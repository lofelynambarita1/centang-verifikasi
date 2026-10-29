<?php

namespace App\Http\Controllers;

use App\Enums\ProjectRole;
use App\Http\Requests\AssignAuditPrinciplesRequest;
use App\Http\Requests\StoreAuditRequest;
use App\Http\Requests\UpdateAuditItemRequest;
use App\Models\Audit;
use App\Models\AuditHasil;
use App\Models\AuditItem;
use App\Models\AuditProjectMember;
use App\Models\Company;
use App\Models\User;
use App\Notifications\AuditSystemNotification;
use App\Support\IspoChecklist;
use App\Support\NotificationDispatcher;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class AuditController extends Controller
{
    public function create(): View
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $companies = Company::orderBy('name')->get();
        $auditors = User::query()
            ->where('role', 'auditor')
            ->whereNull('requested_role')
            ->orderBy('name')
            ->get();
        $selectedCompanyId = request()->integer('company_id') ?: null;

        return view('audits.create', compact('companies', 'auditors', 'selectedCompanyId'));
    }

    public function store(StoreAuditRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $company = Company::findOrFail($validated['company_id']);
        $leadAuditor = User::findOrFail($validated['lead_auditor_id']);

        $audit = DB::transaction(function () use ($validated, $company, $leadAuditor): Audit {
            $title = ! empty($validated['title'])
                ? $validated['title']
                : "Audit Verifikasi ISPO {$company->name}";

            $scope = ! empty($validated['scope'])
                ? $validated['scope']
                : 'Standar ISPO';

            $audit = Audit::create([
                'company_id' => $company->id,
                'title' => $title,
                'status' => 'pending',
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'due_date' => $validated['end_date'],
                'scope' => $scope,
                'auditor_name' => $leadAuditor->name,
            ]);

            // Buat checklist kriteria ISPO terkait secara otomatis (7 prinsip)
            $items = collect(IspoChecklist::items())
                ->map(fn (array $item, int $pos): array => $item + [
                    'sort_order' => $pos,
                    'status' => 'pending',
                ])
                ->values()
                ->all();

            $audit->items()->createMany($items);

            // Daftarkan Lead Auditor langsung ke tabel AuditProjectMember dengan project_role = 'lead_audit'
            AuditProjectMember::create([
                'audit_id' => $audit->id,
                'user_id' => $leadAuditor->id,
                'project_role' => ProjectRole::LeadAudit->value,
                'added_by' => auth()->id(),
            ]);

            return $audit;
        });

        return redirect()->route('audits.assignments.edit', $audit)
            ->with('success', "Proyek audit untuk {$company->name} berhasil dibuat. Silakan atur penugasan prinsip untuk tim auditor.");
    }

    public function assignmentsIndex(): View
    {
        abort_unless(auth()->user()?->canManageAssignments(), 403);

        $audits = Audit::query()
            ->with([
                'company:id,name',
                'items:id,audit_id,title,status',
                'leadMember.user:id,name,email',
                'members.user:id,name,role,requested_role',
            ])
            ->latest()
            ->get()
            ->filter(fn (Audit $audit): bool => $audit->items->contains(
                fn (AuditItem $item): bool => preg_match('/^[1-7]\.\d+\.\d+/', $item->title) === 1,
            ))
            ->values();

        return view('audits.assignments', ['audits' => $audits]);
    }

    public function assignmentsEdit(Audit $audit): View
    {
        abort_unless(auth()->user()?->canManageAssignments($audit), 403);

        $audit->load([
            'company:id,name',
            'assignees',
            'items:id,audit_id,title',
            'leadMember.user:id,name,email,role',
            'members.user:id,name,email,role',
        ]);
        abort_unless($audit->items->contains(
            fn (AuditItem $item): bool => preg_match('/^[1-7]\.\d+\.\d+/', $item->title) === 1,
        ), 404);

        $availableAuditors = User::query()
            ->whereNull('requested_role')
            ->where('role', '!=', 'admin')
            ->orderBy('name')
            ->get();

        $teamAuditors = $audit->teamAuditors();

        return view('audits.assignment-edit', [
            'audit' => $audit,
            'availableAuditors' => $availableAuditors,
            'teamAuditors' => $teamAuditors,
            'projectAuditors' => $teamAuditors,
            'users' => $teamAuditors,
        ]);
    }

    public function show(Audit $audit): View
    {
        $audit->load(['company', 'findings', 'assignees', 'leadMember.user']);
        $canDownloadReport = auth()->user() !== null && $audit->userCanAccessReport(auth()->user());
        $allItems = $audit->items()->with('hasils')->get();
        $isIspoChecklist = $allItems->contains(fn (AuditItem $item): bool => preg_match('/^[1-7]\.\d+\.\d+/', $item->title) === 1);

        if ($isIspoChecklist) {
            $principles = collect(range(1, 7))->mapWithKeys(function (int $number) use ($allItems): array {
                return [$number => $allItems->filter(fn (AuditItem $item): bool => (int) $item->title === $number)->values()];
            });
            $currentPage = max(1, min(request()->integer('page', 1), 7));
            $items = $principles->get($currentPage, collect());
        } else {
            $principles = collect();
            $currentPage = request()->integer('page', 1);
            $items = $audit->items()->paginate(10)->withQueryString();
        }

        return view('audits.show', [
            'audit' => $audit,
            'items' => $items,
            'allItems' => $allItems,
            'principles' => $principles,
            'isIspoChecklist' => $isIspoChecklist,
            'currentPage' => $currentPage,
            'canDownloadReport' => $canDownloadReport,
        ]);
    }

    public function assignPrinciples(
        AssignAuditPrinciplesRequest $request,
        Audit $audit,
        NotificationDispatcher $notifications,
    ): RedirectResponse {
        abort_unless($request->user()?->canManageAssignments($audit), 403);

        $audit->loadMissing('company');
        $validated = $request->validated();

        // 1. Tentukan Lead Auditor proyek
        $leadAuditorId = null;
        if (array_key_exists('lead_auditor_id', $validated)) {
            $leadAuditorId = ! empty($validated['lead_auditor_id']) ? (int) $validated['lead_auditor_id'] : null;
        } elseif ($audit->leadMember) {
            $leadAuditorId = (int) $audit->leadMember->user_id;
        }

        // 2. Periksa anggota tim proyek yang dipilih
        $teamMemberIds = collect($validated['team_members'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->reject(fn (int $id): bool => $id === $leadAuditorId)
            ->unique()
            ->values()
            ->all();

        $allowedMemberIds = array_values(array_unique(array_filter(array_merge(
            $leadAuditorId ? [$leadAuditorId] : [],
            $teamMemberIds,
        ))));

        if ($leadAuditorId) {
            AuditProjectMember::where('audit_id', $audit->id)
                ->whereIn('project_role', [ProjectRole::LeadAudit->value, ProjectRole::LeadAuditor->value])
                ->where('user_id', '!=', $leadAuditorId)
                ->delete();

            AuditProjectMember::updateOrCreate(
                ['audit_id' => $audit->id, 'user_id' => $leadAuditorId],
                [
                    'project_role' => ProjectRole::LeadAudit->value,
                    'added_by' => auth()->id(),
                ]
            );
        } else {
            AuditProjectMember::where('audit_id', $audit->id)
                ->whereIn('project_role', [ProjectRole::LeadAudit->value, ProjectRole::LeadAuditor->value])
                ->delete();
        }

        foreach ($teamMemberIds as $memberId) {
            AuditProjectMember::updateOrCreate(
                ['audit_id' => $audit->id, 'user_id' => $memberId],
                [
                    'project_role' => ProjectRole::Auditor->value,
                    'added_by' => auth()->id(),
                ]
            );
        }

        AuditProjectMember::where('audit_id', $audit->id)
            ->whereNotIn('user_id', $allowedMemberIds)
            ->delete();
        $audit->assignees()->whereNotIn('users.id', $allowedMemberIds)->detach();

        // Ambil daftar anggota tim proyek yang sah
        $currentProjectMemberIds = AuditProjectMember::where('audit_id', $audit->id)
            ->pluck('user_id')
            ->all();

        // 3. Kelola penugasan prinsip audit
        $previousAssignments = $audit->assignees()->get()
            ->map(fn (User $user): string => $user->id.':'.$user->pivot->principle_number)
            ->all();

        $assignments = collect($validated['assignments'] ?? [])
            ->filter(fn (array $assignment): bool => ! empty($assignment['user_id']))
            ->filter(fn (array $assignment): bool => in_array((int) $assignment['user_id'], $currentProjectMemberIds, true))
            ->map(fn (array $assignment): array => [
                'user_id' => (int) $assignment['user_id'],
                'principle_number' => (int) $assignment['principle'],
            ])
            ->values()
            ->all();

        $newAssignments = collect($assignments)
            ->reject(fn (array $assignment): bool => in_array(
                $assignment['user_id'].':'.$assignment['principle_number'],
                $previousAssignments,
                true,
            ))
            ->groupBy('user_id');

        $audit->assignees()->detach();
        foreach ($assignments as $assignment) {
            $audit->assignees()->attach($assignment['user_id'], [
                'principle_number' => $assignment['principle_number'],
            ]);
        }

        // 4. Kirim notifikasi
        $assignedUsers = User::query()
            ->whereNull('requested_role')
            ->whereIn('id', $newAssignments->keys())
            ->get()
            ->keyBy('id');

        foreach ($newAssignments as $userId => $userAssignments) {
            $user = $assignedUsers->get((int) $userId);
            if (! $user) {
                continue;
            }

            $principles = $userAssignments->pluck('principle_number')->unique()->sort()->values();
            $notifications->send([$user], new AuditSystemNotification(
                'principle_assignment',
                'Penugasan prinsip baru',
                "Anda ditugaskan pada Prinsip {$principles->join(', ')} untuk audit {$audit->company->name}.",
                route('audits.show', ['audit' => $audit, 'page' => $principles->first()], false),
            ));
        }

        return redirect()->route('audits.assignments.index')
            ->with('success', 'Penugasan tim audit berhasil diperbarui.');
    }

    public function updateItem(
        UpdateAuditItemRequest $request,
        AuditItem $auditItem,
        NotificationDispatcher $notifications,
    ): RedirectResponse {
        abort_unless($request->user()?->canUpdateChecklist($auditItem), 403);

        $previousStatus = $auditItem->status;
        $validated = $request->validated();
        $auditItem->update($validated);
        $audit = $auditItem->audit()->with('company')->firstOrFail();
        $audit->update(['status' => 'in_progress']);

        if ($validated['status'] === 'non_compliant' && $previousStatus !== 'non_compliant') {
            $this->notifyAuditManagers(
                $notifications,
                (int) $request->user()->getAuthIdentifier(),
                $audit,
                $auditItem,
                'audit_finding',
                'Temuan audit baru',
                "{$request->user()->name} menandai indikator {$auditItem->title} sebagai Tidak Memenuhi di {$audit->company->name}.",
            );
        }

        return redirect()->route('audits.show', [
            'audit' => $auditItem->audit_id,
            'page' => $request->integer('page', 1),
        ])->with('success', 'Hasil verifikasi tersimpan.');
    }

    public function storeEvidence(Request $request, AuditItem $auditItem): RedirectResponse
    {
        abort_unless($request->user()?->canWorkOn($auditItem), 403);

        $request->validate(['evidence' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240']]);
        $auditItem->update(['evidence_path' => $request->file('evidence')->store('audit-evidence', 'public')]);

        return back()->with('success', 'Bukti pendukung berhasil diunggah.');
    }

    public function storeHasil(
        Request $request,
        AuditItem $auditItem,
        NotificationDispatcher $notifications,
    ): RedirectResponse {
        abort_unless($request->user()?->canWorkOn($auditItem), 403);

        $validated = $this->validateHasil($request);
        $audit = $auditItem->audit()->with('company')->firstOrFail();

        $auditItem->hasils()->create([
            'content' => $validated['content'] ?? null,
            'evidence_path' => $request->hasFile('evidence')
                ? $request->file('evidence')->store('audit-evidence', 'public')
                : null,
            'sort_order' => ((int) $auditItem->hasils()->max('sort_order')) + 1,
        ]);
        $audit->update(['status' => 'in_progress']);
        $this->notifyAuditManagers(
            $notifications,
            (int) $request->user()->getAuthIdentifier(),
            $audit,
            $auditItem,
            'audit_result',
            'Hasil audit baru',
            "{$request->user()->name} menambahkan hasil untuk indikator {$auditItem->title} di {$audit->company->name}.",
        );

        return back()->with('success', 'Hasil audit ditambahkan dan tersimpan sebagai draft.');
    }

    public function updateHasil(Request $request, AuditHasil $auditHasil): RedirectResponse
    {
        abort_unless($request->user()?->canWorkOn($auditHasil->auditItem), 403);

        $validated = $this->validateHasil($request);

        $auditHasil->update(['content' => $validated['content'] ?? null]);

        if ($request->hasFile('evidence')) {
            if ($auditHasil->evidence_path) {
                Storage::disk('public')->delete($auditHasil->evidence_path);
            }

            $auditHasil->update(['evidence_path' => $request->file('evidence')->store('audit-evidence', 'public')]);
        }

        $auditHasil->auditItem->audit()->update(['status' => 'in_progress']);

        return back()->with('success', 'Hasil audit tersimpan sebagai draft.');
    }

    public function destroyHasil(AuditHasil $auditHasil): RedirectResponse
    {
        abort_unless(request()->user()?->canWorkOn($auditHasil->auditItem), 403);

        $audit = $auditHasil->auditItem->audit;

        if ($auditHasil->evidence_path) {
            Storage::disk('public')->delete($auditHasil->evidence_path);
        }

        $auditHasil->delete();
        $audit->update(['status' => 'in_progress']);

        return back()->with('success', 'Hasil audit dihapus.');
    }

    /**
     * @return array{content?: ?string, evidence?: mixed}
     */
    private function validateHasil(Request $request): array
    {
        return $request->validate([
            'content' => ['nullable', 'string', 'max:5000', 'required_without:evidence'],
            'evidence' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240', 'required_without:content'],
        ]);
    }

    private function notifyAuditManagers(
        NotificationDispatcher $notifications,
        int $actorId,
        Audit $audit,
        AuditItem $auditItem,
        string $event,
        string $title,
        string $message,
    ): void {
        $leadUser = $audit->leadMember?->user;
        $recipients = User::query()
            ->whereNull('requested_role')
            ->whereIn('role', ['admin', 'lead_audit'])
            ->where('id', '!=', $actorId)
            ->get();

        if ($leadUser && $leadUser->id !== $actorId && $leadUser->requested_role === null) {
            $recipients->push($leadUser);
            $recipients = $recipients->unique('id');
        }

        $notifications->send($recipients, new AuditSystemNotification(
            $event,
            $title,
            $message,
            route('audits.show', [
                'audit' => $audit,
                'page' => max(1, $auditItem->principleNumber()),
            ], false),
        ));
    }

    public function complete(Audit $audit, NotificationDispatcher $notifications): RedirectResponse
    {
        abort_unless(auth()->user()?->isLeadAuditor($audit) || auth()->user()?->isAdmin(), 403, 'Hanya Lead Auditor atau Admin yang berhak menyelesaikan audit.');

        $audit->load(['items', 'company']);
        abort_if($audit->items->contains('status', 'pending'), 422, 'Semua item checklist harus diisi terlebih dahulu.');

        $audit->update(['status' => 'completed', 'completed_at' => now()]);

        $actorId = (int) auth()->id();
        $leadUser = $audit->leadMember?->user;
        $recipients = $audit->assignees()->whereNull('requested_role')->get()
            ->merge(User::query()
                ->whereNull('requested_role')
                ->whereIn('role', ['admin', 'lead_audit'])
                ->get());

        if ($leadUser && $leadUser->requested_role === null) {
            $recipients->push($leadUser);
        }

        $recipients = $recipients->unique('id')
            ->reject(fn (User $user): bool => $user->id === $actorId);

        $notifications->send($recipients, new AuditSystemNotification(
            'audit_completed',
            'Audit selesai',
            "Audit {$audit->company->name} telah ditandai selesai.",
            route('audits.show', ['audit' => $audit], false),
        ));

        return back()->with('success', 'Audit berhasil diselesaikan dan siap dicetak.');
    }

    public function report(Audit $audit): View
    {
        abort_unless(auth()->user() !== null && $audit->userCanAccessReport(auth()->user()), 403);

        return view('audits.report', [
            'audit' => $audit->load([
                'company',
                'items.hasils',
                'findings',
                'assignees',
                'leadMember.user',
                'members.user',
            ]),
        ]);
    }

    public function pdf(Audit $audit, Request $request): mixed
    {
        abort_unless(auth()->user() !== null && $audit->userCanAccessReport(auth()->user()), 403);

        set_time_limit(300);

        $audit->load([
            'company',
            'items.hasils',
            'findings',
            'assignees',
            'leadMember.user',
            'members.user',
        ]);
        $items = $audit->items;
        $isIspoChecklist = $items->contains(fn (AuditItem $item): bool => preg_match('/^[1-7]\.\d+\.\d+/', $item->title) === 1);
        $principles = $isIspoChecklist
            ? collect(range(1, 7))->mapWithKeys(fn (int $number): array => [
                $number => $items->filter(fn (AuditItem $item): bool => (int) $item->title === $number)->values(),
            ])
            : collect();
        $principleSummaries = collect(range(1, 7))->mapWithKeys(function (int $number) use ($items): array {
            $principleItems = $items->filter(fn (AuditItem $item): bool => $item->principleNumber() === $number);

            return [$number => [
                'total' => $principleItems->count(),
                'compliant' => $principleItems->where('status', 'compliant')->count(),
                'non_compliant' => $principleItems->where('status', 'non_compliant')->count(),
                'not_applicable' => $principleItems->where('status', 'not_applicable')->count(),
                'pending' => $principleItems->where('status', 'pending')->count(),
            ]];
        });
        $principleAuditors = collect(range(1, 7))->mapWithKeys(fn (int $number): array => [
            $number => $audit->assignees
                ->filter(fn (User $user): bool => (int) $user->pivot->principle_number === $number)
                ->pluck('name')
                ->unique()
                ->values(),
        ]);
        $leadAuditor = $audit->leadMember?->user;
        $assignedAuditorNames = $audit->assignees
            ->reject(fn (User $user): bool => $leadAuditor !== null && $user->is($leadAuditor))
            ->pluck('name')
            ->unique()
            ->values()
            ->join(', ');

        $pdf = Pdf::loadView('audits.pdf', [
            'audit' => $audit,
            'items' => $items,
            'isIspoChecklist' => $isIspoChecklist,
            'principles' => $principles,
            'principleSummaries' => $principleSummaries,
            'principleAuditors' => $principleAuditors,
            'leadAuditor' => $leadAuditor,
            'assignedAuditorNames' => $assignedAuditorNames,
        ])->setPaper('a4', 'portrait');

        $filename = 'laporan-verifikasi-ispo-'.Str::slug($audit->company->name).'.pdf';

        return $request->boolean('download')
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }

    public function download(Audit $audit): mixed
    {
        $audit->load(['company', 'items.hasils']);
        $items = $audit->items;
        $isIspoChecklist = $items->contains(fn (AuditItem $item): bool => preg_match('/^[1-7]\.\d+\.\d+/', $item->title) === 1);
        $compliant = $items->where('status', 'compliant')->count();
        $nonCompliant = $items->where('status', 'non_compliant')->count();
        $notApplicable = $items->where('status', 'not_applicable')->count();
        $verified = $compliant + $nonCompliant + $notApplicable;
        $totalItems = $items->count();
        $statusLabels = [
            'compliant' => 'Memenuhi',
            'non_compliant' => 'Tidak Memenuhi',
            'not_applicable' => 'NA',
            'pending' => 'Belum diperiksa',
        ];
        $principleTitles = [
            1 => 'KEPATUHAN TERHADAP PERATURAN PERUNDANG-UNDANGAN',
            2 => 'PENERAPAN PRAKTEK PERKEBUNAN YANG BAIK',
            3 => 'PENGELOLAAN LINGKUNGAN HIDUP, SUMBER DAYA ALAM, DAN KEANEKARAGAMAN HAYATI',
            4 => 'TANGGUNG JAWAB KETENAGAKERJAAN',
            5 => 'TANGGUNG JAWAB SOSIAL DAN PEMBERDAYAAN EKONOMI MASYARAKAT',
            6 => 'PENERAPAN TRANSPARANSI',
            7 => 'PENINGKATAN USAHA SECARA BERKELANJUTAN',
        ];

        $document = new PhpWord;
        $document->setDefaultFontName('Arial');
        $document->setDefaultFontSize(10);
        $section = $document->addSection(['marginTop' => 900, 'marginBottom' => 900, 'marginLeft' => 1100, 'marginRight' => 1100]);

        $section->addText('PT. GLOBAL INSPEKSI SERTIFIKASI', ['bold' => true, 'size' => 17, 'color' => '08755F']);
        $section->addText('Inspection & Certification Body', ['italic' => true, 'size' => 10, 'color' => '666666']);
        $section->addTextBreak(1);
        $section->addText('LAPORAN VERIFIKASI HASIL ISPO', ['bold' => true, 'size' => 14], ['alignment' => 'center']);
        $section->addText('('.strtoupper($audit->company->name ?: 'AUDITEE / NAMA KLIEN').')', ['size' => 12], ['alignment' => 'center']);
        $section->addTextBreak(1);

        $facts = [
            'Nama Perusahaan' => $audit->company->name ?: '-',
            'Alamat Perusahaan' => $audit->company->address ?: '-',
            'Bidang Usaha' => $audit->company->industry ?: '-',
            'Auditor' => $audit->auditorNamesText() ?: '-',
            'Tanggal Audit' => $audit->due_date?->locale('id')->translatedFormat('d F Y') ?: '-',
        ];
        $factTable = $section->addTable(['borderSize' => 0, 'cellMargin' => 70]);
        foreach ($facts as $label => $value) {
            $row = $factTable->addRow();
            $row->addCell(3300)->addText($label, ['bold' => true]);
            $row->addCell(500)->addText(':');
            $row->addCell(6300)->addText($value);
        }
        $section->addTextBreak(1);

        $section->addText('I. HASIL PENILAIAN', ['bold' => true, 'size' => 12, 'color' => '08755F']);
        $section->addText('Ringkasan Penilaian', ['bold' => true, 'size' => 10]);
        $section->addText('Dalam kegiatan penilaian atau audit ISPO tahap 2 ini ditemukan beberapa ketidaksesuaian yang terkait dengan persyaratan yang ditetapkan oleh aturan ISPO dalam Peraturan Menteri Pertanian Nomor 33 Tahun 2025 tentang Sertifikasi Kelapa Sawit Berkelanjutan Indonesia terhadap Usaha Perkebunan Kelapa Sawit. Ketidaksesuaian tersebut dicatat dalam daftar untuk perbaikan, dan pihak '.($audit->company->name ?: 'perusahaan').' telah melakukan tindakan perbaikan untuk pemenuhan ketidaksesuaian tersebut.');
        $section->addText('Berikut merupakan ringkasan hasil penilaian berdasarkan prinsip, kriteria, dan indikator ISPO yang berlaku.');

        $summaryTable = $section->addTable(['borderSize' => 6, 'borderColor' => 'D9E5DF', 'cellMargin' => 70]);
        $summaryTable->addRow();
        foreach (['Total indikator', 'Memenuhi', 'Tidak memenuhi', 'NA', 'Terisi'] as $heading) {
            $summaryTable->addCell(2000, ['bgColor' => 'E8F5EF'])->addText($heading, ['bold' => true]);
        }
        $summaryTable->addRow();
        foreach ([$totalItems, $compliant, $nonCompliant, $notApplicable, $verified.' / '.$totalItems] as $value) {
            $summaryTable->addCell(2000)->addText((string) $value);
        }
        $section->addTextBreak(1);

        $section->addText('II. HASIL VERIFIKASI PENILAIAN', ['bold' => true, 'size' => 12, 'color' => '08755F']);

        $groups = $isIspoChecklist
            ? collect(range(1, 7))->mapWithKeys(fn (int $number): array => [
                $number => $items->filter(fn (AuditItem $item): bool => (int) $item->title === $number)->values(),
            ])
            : collect([0 => $items->values()]);

        foreach ($groups as $number => $group) {
            if ($group->isEmpty()) {
                continue;
            }

            if ($isIspoChecklist) {
                $section->addText('PRINSIP '.$number.' — '.($principleTitles[$number] ?? ''), ['bold' => true, 'size' => 11, 'color' => '123C32']);
            }

            $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'D9E5DF', 'cellMargin' => 60]);
            $table->addRow();
            foreach (['No', 'Indikator', 'Parameter', 'Hasil Audit', 'Status'] as $heading) {
                $table->addCell($heading === 'No' ? 600 : 2000, ['bgColor' => 'E8F5EF'])->addText($heading, ['bold' => true]);
            }

            $position = 0;
            foreach ($group as $item) {
                $position++;
                preg_match('/^(\d+\.\d+\.\d+)\s*(.*)$/s', $item->title, $parts);

                $table->addRow();
                $table->addCell(600)->addText((string) $position);
                $table->addCell(4600)->addText(($parts[1] ?? $position).' '.($parts[2] ?? $item->title));
                $table->addCell(2600)->addText($item->description ?: '-');

                $hasilCell = $table->addCell(2000);
                if ($item->hasils->isEmpty()) {
                    $hasilCell->addText($item->note ?: '-');
                } else {
                    foreach ($item->hasils as $hasil) {
                        $hasilCell->addText($hasil->content ?: '(tanpa keterangan)');
                        if ($hasil->evidence_path) {
                            $hasilCell->addText('Bukti: '.basename($hasil->evidence_path), ['size' => 8, 'color' => '555555']);
                        }
                    }
                }

                $table->addCell(1400)->addText($statusLabels[$item->status] ?? 'Belum diperiksa');
            }

            if ($isIspoChecklist) {
                $section->addText('Prinsip '.$number.': Memenuhi '.$group->where('status', 'compliant')->count().' · Tidak memenuhi '.$group->where('status', 'non_compliant')->count().' · NA '.$group->where('status', 'not_applicable')->count(), ['size' => 9, 'color' => '555555']);
                $section->addTextBreak(1);
            }
        }

        if ($nonCompliant > 0) {
            $section->addText('III. TEMUAN AUDIT', ['bold' => true, 'size' => 12, 'color' => '08755F']);
            foreach ($items->where('status', 'non_compliant') as $item) {
                $section->addText('• '.$item->title.' — '.($item->note ?: 'Belum ada catatan tindak lanjut.'));
            }
            $section->addTextBreak(1);
        }

        $section->addTextBreak(2);
        $signatures = $section->addTable(['borderSize' => 0, 'cellMargin' => 70]);
        $signatures->addRow();
        $left = $signatures->addCell(5000);
        $right = $signatures->addCell(5000);

        foreach ([$left, $right] as $cell) {
            $cell->addText('');
            $cell->addText('');
            $cell->addText('');
            $cell->addText('');
            $cell->addText('(ttd)', ['bold' => true], ['alignment' => 'center']);
            $cell->addText('');
        }
        $left->addText($audit->auditorNamesText() ?: '(Nama Auditor)', [], ['alignment' => 'center']);
        $left->addText('Auditor', ['size' => 9, 'color' => '555555'], ['alignment' => 'center']);
        $right->addText('(Nama Pimpinan Perusahaan)', [], ['alignment' => 'center']);
        $right->addText($audit->company->name ?: 'Pimpinan Perusahaan', ['size' => 9, 'color' => '555555'], ['alignment' => 'center']);

        $section->addTextBreak(2);
        $section->addText('Status audit: '.($audit->status === 'completed' ? 'Selesai' : 'Belum selesai').'  ·  Dokumen dihasilkan otomatis dari sistem verifikasi audit ISPO pada '.now()->locale('id')->translatedFormat('d F Y H:i').'.', ['size' => 8, 'color' => '888888']);

        return response()->streamDownload(function () use ($document) {
            IOFactory::createWriter($document, 'Word2007')->save('php://output');
        }, 'laporan-audit-'.$audit->id.'.docx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }
}

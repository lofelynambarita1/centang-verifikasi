<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignAuditPrinciplesRequest;
use App\Http\Requests\UpdateAuditItemRequest;
use App\Models\Audit;
use App\Models\AuditHasil;
use App\Models\AuditItem;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class AuditController extends Controller
{
    public function assignmentsIndex(): View
    {
        $audits = Audit::query()
            ->with(['company:id,name', 'items:id,audit_id,title'])
            ->withCount('assignees')
            ->latest()
            ->get()
            ->filter(fn (Audit $audit): bool => $audit->items->contains(
                fn (AuditItem $item): bool => preg_match('/^[1-7]\.\d+\.\d+/', $item->title) === 1,
            ))
            ->values();

        return view('audits.assignments', ['audits' => $audits]);
    }

    public function show(Audit $audit): View
    {
        $audit->load(['company', 'findings', 'assignees']);
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
            'users' => User::query()->whereIn('role', ['lead_audit', 'auditor'])->orderBy('name')->get(),
        ]);
    }

    public function assignPrinciples(AssignAuditPrinciplesRequest $request, Audit $audit): RedirectResponse
    {
        $assignments = collect($request->validated()['assignments'])
            ->flatMap(fn (array $assignment): array => collect($assignment['users'] ?? [])->unique()->map(fn (int $userId): array => [
                'user_id' => $userId,
                'principle_number' => $assignment['principle'],
            ])->all())
            ->values()
            ->all();

        $audit->assignees()->detach();
        foreach ($assignments as $assignment) {
            $audit->assignees()->attach($assignment['user_id'], [
                'principle_number' => $assignment['principle_number'],
            ]);
        }

        return back()->with('success', 'Penugasan prinsip audit berhasil diperbarui.');
    }

    public function updateItem(UpdateAuditItemRequest $request, AuditItem $auditItem): RedirectResponse
    {
        abort_unless($request->user()?->canWorkOn($auditItem), 403);

        $auditItem->update($request->validated());
        $auditItem->audit()->update(['status' => 'in_progress']);

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

    public function storeHasil(Request $request, AuditItem $auditItem): RedirectResponse
    {
        abort_unless($request->user()?->canWorkOn($auditItem), 403);

        $validated = $this->validateHasil($request);

        $auditItem->hasils()->create([
            'content' => $validated['content'] ?? null,
            'evidence_path' => $request->hasFile('evidence')
                ? $request->file('evidence')->store('audit-evidence', 'public')
                : null,
            'sort_order' => ((int) $auditItem->hasils()->max('sort_order')) + 1,
        ]);
        $auditItem->audit()->update(['status' => 'in_progress']);

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

    public function complete(Audit $audit): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageAssignments(), 403);

        $audit->load('items');
        abort_if($audit->items->contains('status', 'pending'), 422, 'Semua item checklist harus diisi terlebih dahulu.');

        $audit->update(['status' => 'completed', 'completed_at' => now()]);

        return back()->with('success', 'Audit berhasil diselesaikan dan siap dicetak.');
    }

    public function report(Audit $audit): View
    {
        return view('audits.report', ['audit' => $audit->load(['company', 'items.hasils', 'findings'])]);
    }

    public function pdf(Audit $audit): mixed
    {
        set_time_limit(300);

        $audit->load(['company', 'items.hasils', 'findings']);
        $items = $audit->items;
        $isIspoChecklist = $items->contains(fn (AuditItem $item): bool => preg_match('/^[1-7]\.\d+\.\d+/', $item->title) === 1);
        $principles = $isIspoChecklist
            ? collect(range(1, 7))->mapWithKeys(fn (int $number): array => [
                $number => $items->filter(fn (AuditItem $item): bool => (int) $item->title === $number)->values(),
            ])
            : collect();

        $pdf = Pdf::loadView('audits.pdf', [
            'audit' => $audit,
            'items' => $items,
            'isIspoChecklist' => $isIspoChecklist,
            'principles' => $principles,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('laporan-verifikasi-ispo-'.Str::slug($audit->company->name).'.pdf');
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

        $filePath = tempnam(sys_get_temp_dir(), 'audit-').'.docx';
        IOFactory::createWriter($document, 'Word2007')->save($filePath);

        return response()->download($filePath, 'laporan-audit-'.$audit->id.'.docx')->deleteFileAfterSend(true);
    }
}

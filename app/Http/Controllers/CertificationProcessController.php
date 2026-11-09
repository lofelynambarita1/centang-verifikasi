<?php

namespace App\Http\Controllers;

use App\Enums\CertificationCategory;
use App\Http\Requests\UpdateCertificationProcessRequest;
use App\Models\Audit;
use App\Models\CertificationProcess;
use App\Models\Company;
use App\Models\User;
use App\Support\AuditProgressSummary;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificationProcessController extends Controller
{
    public function index(string $category): View
    {
        $category = $this->category($category);
        $user = request()->user();
        $canManageProcess = $user instanceof User && $user->canManageAssignments();

        abort_unless($user instanceof User && ($canManageProcess || $user->isAuditor()), 403);

        $companies = Company::query()
            ->when(
                $canManageProcess,
                fn (Builder $query) => $query->with(['certificationProcesses' => fn (HasMany $processes) => $processes->where('category', $category->value)]),
                fn (Builder $query) => $query
                    ->whereHas('audits.assignees', fn (Builder $assignees) => $assignees->whereKey($user->getKey()))
                    ->withCount([
                        'audits as assigned_audits_count' => fn (Builder $audits) => $audits
                            ->whereHas('assignees', fn (Builder $assignees) => $assignees->whereKey($user->getKey())),
                    ])
            )
            ->orderBy('name')
            ->get();

        return view('certification-process.index', [
            'category' => $category,
            'companies' => $companies,
            'canManageProcess' => $canManageProcess,
        ]);
    }

    public function show(string $category, Company $company): View
    {
        $category = $this->category($category);
        $user = request()->user();
        abort_unless($user instanceof User && $company->canViewAssignedCertificationProgress($user), 404);

        $canManageProcess = $company->canManageCertificationProcess($user);

        if (! $canManageProcess) {
            $assignedAudits = $company->audits()
                ->whereHas('assignees', fn (Builder $assignees) => $assignees->whereKey($user->getKey()))
                ->with([
                    'items',
                    'assignees' => fn (BelongsToMany $assignees) => $assignees->whereKey($user->getKey()),
                ])
                ->orderByDesc('created_at')
                ->get();

            $assignedPrincipleProgress = $assignedAudits
                ->flatMap(function (Audit $audit): array {
                    return $audit->assignees
                        ->map(function (User $assignee) use ($audit): array {
                            $principleNumber = (int) $assignee->pivot->principle_number;
                            $items = $audit->items
                                ->filter(fn ($item): bool => $item->principleNumber() === $principleNumber)
                                ->values();
                            $summary = AuditProgressSummary::forItems($items);

                            return [
                                'audit_title' => $audit->title,
                                'principle_number' => $principleNumber,
                                ...$summary,
                            ];
                        })
                        ->all();
                })
                ->sortBy('principle_number')
                ->values();

            return view('certification-process.show', [
                'category' => $category,
                'company' => $company,
                'canManageProcess' => false,
                'assignedPrincipleProgress' => $assignedPrincipleProgress,
            ]);
        }

        $process = $company->certificationProcesses()
            ->where('category', $category->value)
            ->firstOrNew(['category' => $category->value]);

        $qrCodeDataUri = null;

        if ($process->exists && $process->certificate_document_path && $process->certificate_token) {
            $certificateUrl = route('certificates.show', $process->certificate_token);
            $qrCode = new QrCode(
                data: $certificateUrl,
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 280,
                margin: 12,
            );
            $svg = (new SvgWriter)->write($qrCode)->getString();
            $qrCodeDataUri = 'data:image/svg+xml;base64,'.base64_encode($svg);
        }

        return view('certification-process.show', [
            'category' => $category,
            'company' => $company,
            'canManageProcess' => true,
            'process' => $process,
            'qrCodeDataUri' => $qrCodeDataUri,
        ]);
    }

    public function update(UpdateCertificationProcessRequest $request, string $category, Company $company): RedirectResponse
    {
        $category = $this->category($category);
        $process = $company->certificationProcesses()
            ->firstOrNew(['category' => $category->value]);
        $validated = $request->validated();
        $oldPaths = [];

        foreach ([
            'summary_document' => 'summary_document_path',
            'audit_correction_document' => 'audit_correction_document_path',
            'certificate_document' => 'certificate_document_path',
        ] as $input => $column) {
            if (! $request->hasFile($input)) {
                continue;
            }

            $path = $request->file($input)->store(
                'certification-processes/'.$company->id.'/'.$category->value,
                'local'
            );

            if (! is_string($path)) {
                throw new RuntimeException('Dokumen proses sertifikasi gagal disimpan.');
            }

            if ($process->{$column}) {
                $oldPaths[] = $process->{$column};
            }

            $process->{$column} = $path;

            if ($column === 'certificate_document_path' && ! $process->certificate_token) {
                $process->certificate_token = (string) Str::uuid();
            }
        }

        $process->category = $category;
        if (array_key_exists('audit_report', $validated)) {
            $process->audit_report = $validated['audit_report'];
        }
        if (array_key_exists('review_panel', $validated)) {
            $process->review_panel = $validated['review_panel'];
        }
        $process->save();

        foreach ($oldPaths as $oldPath) {
            if (! Storage::disk('local')->delete($oldPath)) {
                Log::warning('Dokumen sertifikasi lama gagal dihapus setelah penggantian.', [
                    'certification_process_id' => $process->id,
                    'path' => $oldPath,
                ]);
            }
        }

        return redirect()
            ->route('certification-process.show', [$category->value, $company])
            ->with('success', 'Proses sertifikasi berhasil diperbarui.');
    }

    public function download(string $category, Company $company, string $document): StreamedResponse
    {
        $category = $this->category($category);
        $this->authorizeCompany($company);

        $column = match ($document) {
            'summary' => 'summary_document_path',
            'correction' => 'audit_correction_document_path',
            'certificate' => 'certificate_document_path',
            default => abort(404),
        };
        $process = $company->certificationProcesses()
            ->where('category', $category->value)
            ->firstOrFail();
        $path = $process->{$column};

        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download(
            $path,
            $document.'.'.pathinfo($path, PATHINFO_EXTENSION)
        );
    }

    public function certificate(string $token): BinaryFileResponse
    {
        $process = CertificationProcess::query()
            ->where('certificate_token', $token)
            ->firstOrFail();
        $path = $process->certificate_document_path;

        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="certificate.pdf"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function category(string $category): CertificationCategory
    {
        return CertificationCategory::tryFrom($category) ?? abort(404);
    }

    private function authorizeCompany(Company $company): void
    {
        $user = request()->user();

        abort_unless($user instanceof User && $company->canManageCertificationProcess($user), 404);
    }
}

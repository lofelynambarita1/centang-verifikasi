<?php

namespace App\Services;

use App\Exceptions\AuditReportTemplateNotFound;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\TemplateProcessor;

class AuditReportWordService
{
    private const TEMPLATE_PATH = 'templates/ispo/laporan-verifikasi-ispo.docx';

    public function generate(array $data): string
    {
        $templatePath = storage_path('app/'.self::TEMPLATE_PATH);

        if (! File::exists($templatePath)) {
            Log::error('Audit report Word template is missing.', [
                'template_path' => self::TEMPLATE_PATH,
            ]);

            throw new AuditReportTemplateNotFound(self::TEMPLATE_PATH);
        }

        $outputDirectory = 'reports/word';
        File::ensureDirectoryExists(storage_path('app/'.$outputDirectory));

        $companyName = (string) ($data['company']?->name ?? 'Perusahaan');
        $outputPath = storage_path('app/'.$outputDirectory.'/'.Str::uuid().'.docx');

        $template = new TemplateProcessor($templatePath);
        $values = [
            'nama_klien' => $companyName ?: '-',
            'nama_perusahaan' => $companyName ?: '-',
            'alamat_perusahaan' => (string) ($data['company']?->address ?: '-'),
            'luasan_lahan' => $this->landArea($data['project']->land_area),
            'jenis_audit' => $this->auditType($data['project']->audit_type),
            'lead_auditor' => (string) ($data['lead_auditor']?->name ?: '-'),
            'tim_auditor' => $this->auditorTeam($data['auditor_team'] ?? collect()),
            'periode_audit' => (string) ($data['audit_date'] ?: '-'),
            'kode_laporan' => $data['project']->project_code
                ? 'Kode: '.$data['project']->project_code
                : '',
        ];

        foreach (array_intersect($template->getVariables(), array_keys($values)) as $variable) {
            $template->setValue($variable, $values[$variable]);
        }

        $template->saveAs($outputPath);

        return $outputPath;
    }

    private function auditorTeam(Collection $auditorTeam): string
    {
        $names = $auditorTeam->pluck('user.name')->filter()->values()->all();

        return $names !== [] ? implode(', ', $names) : '-';
    }

    private function landArea(mixed $landArea): string
    {
        return $landArea !== null
            ? number_format((float) $landArea, 2, ',', '.').' ha'
            : '-';
    }

    private function auditType(?string $auditType): string
    {
        return match (strtolower(trim((string) $auditType))) {
            'lapangan', 'field' => 'Audit Lapangan',
            'dokumen', 'document' => 'Audit Dokumen',
            default => $auditType ? Str::headline($auditType) : '-',
        };
    }

    public function downloadFilename(array $data): string
    {
        $companyName = (string) ($data['company']?->name ?? 'Perusahaan');
        $safeCompanyName = trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $companyName), '-');

        return 'Laporan-Verifikasi-ISPO-'.($safeCompanyName ?: 'Perusahaan').'.docx';
    }
}

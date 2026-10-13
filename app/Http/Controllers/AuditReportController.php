<?php

namespace App\Http\Controllers;

use App\Exceptions\AuditReportTemplateNotFound;
use App\Models\AuditProject;
use App\Services\AuditReportDataService;
use App\Services\AuditReportWordService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AuditReportController extends Controller
{
    public function __construct(
        private readonly AuditReportDataService $reports,
        private readonly AuditReportWordService $wordReports,
    ) {}

    public function preview(AuditProject $auditProject): View
    {
        $this->authorize('view', $auditProject);

        return view('audit-projects.report-preview', $this->reports->assemble($auditProject));
    }

    public function pdf(AuditProject $auditProject): Response
    {
        $this->authorize('view', $auditProject);

        $data = $this->reports->assemble($auditProject);
        $companyName = (string) ($data['company']?->name ?? 'Perusahaan');
        $safeCompanyName = trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $companyName), '-');
        $filename = 'Laporan-Verifikasi-Hasil-ISPO-'.($safeCompanyName ?: 'Perusahaan').'.pdf';

        $pdf = Pdf::loadView('audit-projects.report-pdf', $data);
        $this->registerCalibriFont($pdf);

        return $pdf->setPaper('letter', 'portrait')->download($filename);
    }

    public function downloadWord(AuditProject $auditProject): BinaryFileResponse
    {
        $this->authorize('view', $auditProject);

        $data = $this->reports->assemble($auditProject);

        try {
            $path = $this->wordReports->generate($data);
        } catch (AuditReportTemplateNotFound) {
            abort(404, 'Template laporan Word tidak ditemukan.');
        }

        return response()
            ->download($path, $this->wordReports->downloadFilename($data))
            ->deleteFileAfterSend(true);
    }

    private function registerCalibriFont(\Barryvdh\DomPDF\PDF $pdf): void
    {
        $fontDirectory = 'C:\\Windows\\Fonts';
        $fonts = [
            ['file' => 'calibri.ttf', 'style' => ['weight' => 'normal', 'style' => 'normal']],
            ['file' => 'calibrib.ttf', 'style' => ['weight' => 'bold', 'style' => 'normal']],
            ['file' => 'calibrii.ttf', 'style' => ['weight' => 'normal', 'style' => 'italic']],
            ['file' => 'calibriz.ttf', 'style' => ['weight' => 'bold', 'style' => 'italic']],
        ];
        $dompdf = $pdf->getDomPDF();
        $options = $dompdf->getOptions();
        $options->setChroot(array_unique([...$options->getChroot(), $fontDirectory]));
        $fontCacheDirectory = $options->getFontDir();

        if (! is_dir($fontCacheDirectory) && ! mkdir($fontCacheDirectory, 0755, true) && ! is_dir($fontCacheDirectory)) {
            throw new RuntimeException('Direktori cache font DomPDF tidak dapat dibuat: '.$fontCacheDirectory);
        }

        $fontMetrics = $dompdf->getFontMetrics();

        foreach ($fonts as $font) {
            $path = $fontDirectory.'\\'.$font['file'];

            if (! is_file($path) || ! is_readable($path)) {
                throw new RuntimeException('Font Calibri tidak ditemukan atau tidak dapat dibaca: '.$path);
            }

            if (! $fontMetrics->registerFont([
                'family' => 'Calibri',
                ...$font['style'],
            ], 'file://'.$path)) {
                throw new RuntimeException('Font Calibri gagal didaftarkan: '.$path);
            }
        }
    }
}

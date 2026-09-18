<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAuditItemRequest;
use App\Models\Audit;
use App\Models\AuditItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class AuditController extends Controller
{
    public function show(Audit $audit): View
    {
        $audit->load(['company', 'findings']);
        $allItems = $audit->items()->get();
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
        ]);
    }

    public function updateItem(UpdateAuditItemRequest $request, AuditItem $auditItem): RedirectResponse
    {
        $auditItem->update($request->validated());
        $auditItem->audit()->update(['status' => 'in_progress']);

        return redirect()->route('audits.show', [
            'audit' => $auditItem->audit_id,
            'page' => $request->integer('page', 1),
        ])->with('success', 'Hasil verifikasi tersimpan.');
    }

    public function storeEvidence(Request $request, AuditItem $auditItem): RedirectResponse
    {
        $request->validate(['evidence' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240']]);
        $auditItem->update(['evidence_path' => $request->file('evidence')->store('audit-evidence', 'public')]);

        return back()->with('success', 'Bukti pendukung berhasil diunggah.');
    }

    public function complete(Audit $audit): RedirectResponse
    {
        $audit->load('items');
        abort_if($audit->items->contains('status', 'pending'), 422, 'Semua item checklist harus diisi terlebih dahulu.');

        $audit->update(['status' => 'completed', 'completed_at' => now()]);

        return back()->with('success', 'Audit berhasil diselesaikan dan siap dicetak.');
    }

    public function report(Audit $audit): View
    {
        return view('audits.report', ['audit' => $audit->load(['company', 'items', 'findings'])]);
    }

    public function download(Audit $audit): mixed
    {
        $audit->load(['company', 'items']);
        $document = new PhpWord;
        $document->setDefaultFontName('Arial');
        $document->setDefaultFontSize(10);
        $section = $document->addSection(['marginTop' => 900, 'marginBottom' => 900]);
        $section->addText('VERIFIKASI AUDIT ISPO', ['bold' => true, 'size' => 16, 'color' => '08755F']);
        $section->addText('Laporan Audit Verifikasi', ['bold' => true, 'size' => 14]);
        $section->addText($audit->company->name.' - '.$audit->title);
        $section->addTextBreak(1);
        $section->addText('Perusahaan: '.$audit->company->name);
        $section->addText('Auditor: '.($audit->auditor_name ?: '-'));
        $section->addText('Deadline: '.($audit->due_date?->format('d F Y') ?: '-'));
        $section->addTextBreak(1);
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'D9E5DF', 'cellMargin' => 80]);
        $table->addRow();
        foreach (['No.', 'Prinsip verifikasi', 'Hasil', 'Catatan'] as $heading) {
            $table->addCell(1800, ['bgColor' => 'E8F5EF'])->addText($heading, ['bold' => true]);
        }
        foreach ($audit->items as $index => $item) {
            $table->addRow();
            $table->addCell(700)->addText((string) ($index + 1));
            $table->addCell(5000)->addText($item->title."\n".($item->description ?: ''));
            $table->addCell(2200)->addText(match ($item->status) {
                'compliant' => 'Sesuai',
                'non_compliant' => 'Perlu perbaikan',
                'not_applicable' => 'Tidak berlaku',
                default => 'Belum diperiksa',
            });
            $table->addCell(4000)->addText($item->note ?: '-');
        }
        $section->addTextBreak(1);
        $section->addText('Status audit: '.($audit->status === 'completed' ? 'Selesai' : 'Belum selesai'), ['bold' => true]);
        $filePath = tempnam(sys_get_temp_dir(), 'audit-').'.docx';
        IOFactory::createWriter($document, 'Word2007')->save($filePath);

        return response()->download($filePath, 'laporan-audit-'.$audit->id.'.docx')->deleteFileAfterSend(true);
    }
}

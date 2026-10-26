@if ($errors->any())
    <div class="form-error" role="alert">{{ $errors->first() }}</div>
@endif
@php
    $defaultReportSummaryParagraphOne = str_replace(
        ':company',
        $company?->name ?: old('name', 'perusahaan'),
        (string) config('ispo.report_summary.paragraph_one'),
    );
    $defaultReportSummaryParagraphTwo = (string) config('ispo.report_summary.paragraph_two');
@endphp
<div class="form-grid">
    <div class="field">
        <label for="name">Nama perusahaan</label>
        <input id="name" name="name" value="{{ old('name', $company?->name) }}" required>
    </div>
    <div class="field">
        <label for="industry">Bidang usaha</label>
        <input id="industry" name="industry" value="{{ old('industry', $company?->industry) }}">
    </div>
    <div class="field field--wide">
        <label for="address">Alamat</label>
        <textarea id="address" name="address" rows="3">{{ old('address', $company?->address) }}</textarea>
    </div>
    <div class="field field--wide">
        <label for="land_area">Luasan lahan</label>
        <input id="land_area" name="land_area" value="{{ old('land_area', $company?->land_area) }}" placeholder="Contoh: 1.250 ha">
    </div>
    <div class="field">
        <label for="contact_name">Nama PIC</label>
        <input id="contact_name" name="contact_name" value="{{ old('contact_name', $company?->contact_name) }}">
    </div>
    <div class="field">
        <label for="contact_email">Email PIC</label>
        <input id="contact_email" type="email" name="contact_email" value="{{ old('contact_email', $company?->contact_email) }}">
    </div>
    <div class="field">
        <label for="auditor_name">Nama auditor</label>
        <textarea id="auditor_name" name="auditor_name" rows="3" placeholder="Pisahkan nama auditor dengan koma atau baris baru">{{ old('auditor_name', $audit?->auditor_name) }}</textarea>
    </div>
    <div class="field">
        <label for="due_date">Deadline audit</label>
        <input id="due_date" type="date" name="due_date" value="{{ old('due_date', $audit?->due_date?->format('Y-m-d')) }}">
    </div>
    <div class="field field--wide">
        <label for="report_summary_paragraph_one">Ringkasan penilaian - paragraf pertama</label>
        <textarea id="report_summary_paragraph_one" name="report_summary_paragraph_one" rows="5">{{ old('report_summary_paragraph_one', $audit?->reportSummaryParagraphOne() ?? $defaultReportSummaryParagraphOne) }}</textarea>
    </div>
    <div class="field field--wide">
        <label for="report_summary_paragraph_two">Ringkasan penilaian - paragraf kedua</label>
        <textarea id="report_summary_paragraph_two" name="report_summary_paragraph_two" rows="4">{{ old('report_summary_paragraph_two', $audit?->reportSummaryParagraphTwo() ?? $defaultReportSummaryParagraphTwo) }}</textarea>
    </div>
</div>
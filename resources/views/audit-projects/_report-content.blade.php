<section class="report-body-text">
<h2 class="report-heading">I. HASIL PENILAIAN</h2>
<h3 class="report-heading">RINGKASAN PENILAIAN</h3>
<p class="assessment-summary">{{ $assessment_summary ?: 'Ringkasan penilaian belum diisi.' }}</p>
<section class="verification-section">
<h3 class="report-heading">HASIL VERIFIKASI PENILAIAN</h3>

@if ($principles->isNotEmpty())
    @foreach ($principles as $principleData)
        <table class="report-assessment-table verification-table {{ $loop->first ? 'verification-table--first' : 'verification-table--continued' }}">
            <colgroup>
                <col style="width: 2.35cm;">
                <col style="width: 14.16cm;">
            </colgroup>
            <tbody>
        <tr class="verification-table__principle">
            <td>{{ $principleData['principle']->code }}</td>
            <td><strong>{{ $principleData['principle']->title }}</strong></td>
        </tr>
        @foreach ($principleData['sections'] as $sectionData)
            <tr class="verification-table__section">
                <td>{{ $sectionData['section']->code }}</td>
                <td>
                    <strong>{{ $sectionData['section']->title }}</strong>
                    @if ($sectionData['section']->description)
                        <div class="report-text-justify">{{ $sectionData['section']->description }}</div>
                    @endif
                </td>
            </tr>
            @foreach ($sectionData['indicators'] as $indicatorData)
                @php
                    $indicator = $indicatorData['indicator'];
                    $parametersByType = $indicatorData['parameters']->groupBy(fn ($parameter) => $parameter->parameter_type->value);
                    $assessmentStatus = $indicator->result?->assessment_status;
                    $statusLabel = $assessmentStatus?->label() ?? match ($assessmentStatus?->value) {
                        'memenuhi' => 'Memenuhi',
                        'tidak_memenuhi' => 'Tidak Memenuhi',
                        'na' => 'NA',
                        default => 'Belum Dinilai',
                    };
                @endphp
                <tr class="verification-table__indicator">
                    <td>{{ $indicator->code }}</td>
                    <td><div class="report-text-justify">{{ $indicator->title }}</div></td>
                </tr>
                @foreach ($parametersByType as $parameters)
                    @foreach ($parameters->values() as $parameterIndex => $parameter)
                        @php
                            $parameterText = trim((string) preg_replace('/^\s*\d+[\.)]\s*/', '', (string) $parameter->content));
                            $positionClass = match (true) {
                                $loop->first && $loop->last => $parameter->parameter_type->value.'-row-first '.$parameter->parameter_type->value.'-row-last',
                                $loop->first => $parameter->parameter_type->value.'-row-first',
                                $loop->last => $parameter->parameter_type->value.'-row-last',
                                default => $parameter->parameter_type->value.'-row-middle',
                            };
                        @endphp
                        <tr class="{{ $parameter->parameter_type->value }}-row {{ $positionClass }}">
                            <td>{{ $parameterIndex === 0 ? $parameter->parameter_type->label() : '' }}</td>
                            <td><div class="report-text-justify">{{ $parameterIndex + 1 }}. {{ $parameterText }}</div></td>
                        </tr>
                    @endforeach
                @endforeach
                <tr class="verification-table__result">
                    <td></td>
                    <td>
                        <div class="verification-table__result-label">Hasil Audit:</div>
                        <div class="result">{{ $indicator->result?->result_text ?: '-' }}</div>
                        <div class="verification-table__status"><strong>Status</strong> : {{ $statusLabel }}</div>
                    </td>
                </tr>
                @if ($indicatorData['evidences']->isNotEmpty())
                    <tr>
                        <td></td>
                        <td>
                            <strong>Evidence terpilih</strong>
                            @foreach ($indicatorData['evidences'] as $evidence)
                                @if ($evidence->preview_data)
                                    <figure>
                                        <img class="evidence-image" src="{{ $evidence->preview_data }}" alt="{{ $evidence->caption ?: $evidence->original_name }}">
                                        @if ($evidence->caption)<figcaption>{{ $evidence->caption }}</figcaption>@endif
                                    </figure>
                                @else
                                    <div class="attachment">Lampiran: {{ $evidence->original_name }}{{ $evidence->caption ? ' - '.$evidence->caption : '' }}</div>
                                @endif
                            @endforeach
                        </td>
                    </tr>
                @endif
            @endforeach
        @endforeach
            </tbody>
        </table>
    @endforeach
@else
    <p>Belum ada struktur penilaian pada project ini.</p>
@endif
</section>
</section>

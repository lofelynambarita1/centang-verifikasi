<?php

namespace App\Services;

use App\Enums\AuditEvidenceLifecycleStatus;
use App\Models\AuditIndicatorEvidence;
use App\Models\AuditProject;
use App\Models\AuditProjectIndicator;
use Illuminate\Validation\ValidationException;

class AuditResultDocument
{
    /** @var array<int, string> */
    private const InlineAttributes = ['bold', 'italic', 'underline'];

    /** @var array<int, string> */
    private const LineAttributes = ['list', 'indent', 'align'];

    /** @var array<int, string> */
    private const Lists = ['ordered', 'bullet'];

    /** @var array<int, string> */
    private const Alignments = ['left', 'justify'];

    /**
     * @param  array<string, mixed>|null  $document
     * @return array{ops: array<int, array<string, mixed>>}
     */
    public function normalize(?array $document, ?AuditProject $project = null, ?AuditProjectIndicator $indicator = null): array
    {
        $document ??= [];
        $ops = $document['ops'] ?? [];

        if (! is_array($ops)) {
            $this->throwInvalid('Format Delta Hasil Audit tidak valid.');
        }

        $normalized = [];
        $plainText = '';
        $hasSupportedContent = false;

        foreach (array_values($ops) as $op) {
            if (! is_array($op) || ! array_key_exists('insert', $op)) {
                continue;
            }

            if (array_diff(array_keys($op), ['insert', 'attributes']) !== []) {
                $this->throwInvalid('Hasil Audit berisi format yang tidak didukung.');
            }

            $insertValue = $op['insert'];
            if ($insertValue === null) {
                continue;
            }
            if (is_array($insertValue) && array_key_exists('auditEvidence', $insertValue)) {
                $normalized[] = $this->normalizeEvidenceEmbed($insertValue['auditEvidence'], $project, $indicator);
                $hasSupportedContent = true;

                continue;
            }
            if (is_array($insertValue) && is_string($insertValue['insert'] ?? null)) {
                $insertValue = $insertValue['insert'];
            }

            if (! is_string($insertValue)) {
                $this->throwInvalid('Hasil Audit hanya mendukung teks dan gambar evidence.');
            }

            $insert = str_contains($insertValue, "\n")
                ? $this->safeText($insertValue)
                : trim($this->safeText($insertValue));
            $attributes = $this->normalizeAttributes($op['attributes'] ?? []);
            $normalizedOp = ['insert' => $insert];

            if ($attributes !== []) {
                $normalizedOp['attributes'] = $attributes;
            }

            $normalized[] = $normalizedOp;
            $plainText .= $insert;
            if (trim($insert) !== '') {
                $hasSupportedContent = true;
            }
        }

        if (! $hasSupportedContent) {
            $this->throwInvalid('Hasil Audit tidak boleh kosong.');
        }

        return ['ops' => $normalized];
    }

    /**
     * @param  array{ops: array<int, array<string, mixed>>}  $document
     */
    public function toPlainText(array $document): string
    {
        $counters = [1 => 0, 2 => 0, 3 => 0];
        $lines = [];
        $line = '';

        foreach ($document['ops'] as $op) {
            if (! is_string($op['insert'])) {
                continue;
            }

            $parts = explode("\n", $op['insert']);
            $attributes = $op['attributes'] ?? [];

            foreach ($parts as $index => $part) {
                $line .= $part;

                if ($index === count($parts) - 1) {
                    continue;
                }

                $text = trim($line);
                $line = '';

                if ($text === '') {
                    continue;
                }

                if (($attributes['list'] ?? null) !== null) {
                    $level = min(3, ((int) ($attributes['indent'] ?? 0)) + 1);
                    $counters[$level]++;

                    for ($resetLevel = $level + 1; $resetLevel <= 3; $resetLevel++) {
                        $counters[$resetLevel] = 0;
                    }

                    $lines[] = str_repeat('   ', $level - 1).$this->markerForLevel($level, $counters[$level]).' '.$text;
                } else {
                    $counters = [1 => 0, 2 => 0, 3 => 0];
                    $lines[] = $text;
                }
            }
        }

        if (trim($line) !== '') {
            $lines[] = trim($line);
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @return array{insert: array{auditEvidence: array{evidence_id: int}}}
     */
    private function normalizeEvidenceEmbed(mixed $value, ?AuditProject $project, ?AuditProjectIndicator $indicator): array
    {
        if (! is_array($value)) {
            $this->throwInvalid('Referensi gambar evidence tidak valid.');
        }

        if (array_diff(array_keys($value), ['evidence_id']) !== []) {
            $this->throwInvalid('Referensi gambar evidence tidak valid.');
        }

        $evidenceId = filter_var($value['evidence_id'] ?? null, FILTER_VALIDATE_INT);
        if (! is_int($evidenceId) || $evidenceId < 1) {
            $this->throwInvalid('Referensi gambar evidence tidak valid.');
        }

        if ($project === null || $indicator === null) {
            return ['insert' => ['auditEvidence' => ['evidence_id' => $evidenceId]]];
        }

        $evidence = AuditIndicatorEvidence::query()
            ->whereKey($evidenceId)
            ->where('audit_project_id', $project->id)
            ->where('audit_project_indicator_id', $indicator->id)
            ->where('lifecycle_status', AuditEvidenceLifecycleStatus::Active->value)
            ->first();

        if (! $evidence) {
            $this->throwInvalid('Evidence gambar tidak ditemukan untuk indikator ini.');
        }

        if (! $this->isSupportedImageEvidence($evidence)) {
            $this->throwInvalid('Untuk saat ini, hanya file gambar yang dapat disisipkan langsung ke Hasil Audit.');
        }

        return ['insert' => ['auditEvidence' => ['evidence_id' => $evidenceId]]];
    }

    private function isSupportedImageEvidence(AuditIndicatorEvidence $evidence): bool
    {
        return in_array(strtolower((string) $evidence->mime_type), ['image/jpeg', 'image/png', 'image/webp'], true)
            && in_array(strtolower((string) $evidence->extension), ['jpg', 'jpeg', 'png', 'webp'], true);
    }

    /**
     * @return array<string, bool|int|string>
     */
    private function normalizeAttributes(mixed $attributes): array
    {
        if (! is_array($attributes)) {
            return [];
        }

        $normalized = [];
        foreach ($attributes as $key => $value) {
            $key = (string) $key;

            if (in_array($key, self::InlineAttributes, true)) {
                if ($value === true) {
                    $normalized[$key] = true;
                } elseif ($value !== false && $value !== null) {
                    $this->throwInvalid('Format teks Hasil Audit tidak didukung.');
                }

                continue;
            }

            if ($key === 'list') {
                if (! in_array($value, self::Lists, true)) {
                    $this->throwInvalid('Jenis daftar Hasil Audit tidak valid.');
                }

                $normalized[$key] = (string) $value;

                continue;
            }

            if ($key === 'indent') {
                $indent = (int) $value;
                if ($indent < 0 || $indent > 2) {
                    $this->throwInvalid('Indent Hasil Audit tidak valid.');
                }

                if ($indent > 0) {
                    $normalized[$key] = $indent;
                }

                continue;
            }

            if ($key === 'align') {
                if (! in_array($value, self::Alignments, true)) {
                    $this->throwInvalid('Alignment Hasil Audit tidak valid.');
                }

                if ($value !== 'left') {
                    $normalized[$key] = (string) $value;
                }

                continue;
            }

            if (! in_array($key, self::LineAttributes, true)) {
                $this->throwInvalid('Hasil Audit berisi format yang tidak didukung.');
            }
        }

        return $normalized;
    }

    private function plainText(string $text): string
    {
        return trim($this->safeText($text));
    }

    private function safeText(string $text): string
    {
        return html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function markerForLevel(int $level, int $number): string
    {
        return match ($level) {
            2 => $this->letterMarker($number).'.',
            3 => $number.')',
            default => $number.'.',
        };
    }

    private function letterMarker(int $number): string
    {
        $letters = '';

        while ($number > 0) {
            $number--;
            $letters = chr(97 + ($number % 26)).$letters;
            $number = intdiv($number, 26);
        }

        return $letters;
    }

    private function throwInvalid(string $message): never
    {
        throw ValidationException::withMessages([
            'result_content' => $message,
        ]);
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Audit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportIspoChecklist extends Command
{
    protected $signature = 'audit:import-pdf
        {audit : The audit ID that will receive the checklist}
        {pdf : The source PDF path}';

    protected $description = 'Import ISPO indicators, parameters, and audit results from a PDF';

    public function handle(): int
    {
        $audit = Audit::find($this->argument('audit'));
        $pdfPath = $this->argument('pdf');

        if (! $audit) {
            $this->error('Audit tidak ditemukan.');

            return self::FAILURE;
        }

        if (! is_file($pdfPath)) {
            $this->error('File PDF tidak ditemukan: '.$pdfPath);

            return self::FAILURE;
        }

        $items = $this->extractItems(file_get_contents($pdfPath));

        if ($items === []) {
            $this->error('Tidak ada indikator ISPO yang berhasil diekstrak dari PDF.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($audit, $items): void {
            $audit->items()->delete();
            $audit->items()->createMany($items);
        });

        $this->info(sprintf('%d indikator berhasil diimpor ke audit #%d.', count($items), $audit->id));

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{sort_order: int, title: string, description: ?string, status: string}>
     */
    private function extractItems(string $pdf): array
    {
        preg_match_all('/<<(.*?)>>\s*stream\r?\n(.*?)\r?\nendstream/s', $pdf, $streams, PREG_SET_ORDER);
        $lines = [];

        foreach ($streams as $streamMatch) {
            if (! str_contains($streamMatch[1], 'FlateDecode')) {
                continue;
            }

            $decoded = @gzuncompress($streamMatch[2]);

            if ($decoded === false) {
                continue;
            }

            $pageLines = [];
            preg_match_all('/BT(.*?)ET/s', $decoded, $blocks);

            foreach ($blocks[1] as $block) {
                if (! preg_match('/1 0 0 1 ([\d.]+) ([\d.]+) Tm/', $block, $position)) {
                    continue;
                }

                $text = $this->decodeText($block);

                if ($text !== '') {
                    $pageLines[] = [
                        'x' => (float) $position[1],
                        'y' => (float) $position[2],
                        'text' => $text,
                    ];
                }
            }

            $lines = array_merge($lines, $this->groupLines($pageLines));
        }

        return $this->buildItems($lines);
    }

    /**
     * @param  array<int, array{x: float, y: float, text: string}>  $lines
     * @return array<int, string>
     */
    private function groupLines(array $lines): array
    {
        $groups = [];

        foreach ($lines as $line) {
            $key = (string) round($line['y'] * 2) / 2;
            $groups[$key][] = $line;
        }

        uksort($groups, fn (string $first, string $second): int => (float) $second <=> (float) $first);
        $result = [];

        foreach ($groups as $group) {
            usort($group, fn (array $first, array $second): int => $first['x'] <=> $second['x']);
            $text = trim(implode(' ', array_column($group, 'text')));
            $text = preg_replace('/\s+/', ' ', $text) ?? $text;
            $text = trim(str_replace('\\', '', $text));

            if ($text !== '' && ! in_array($text, ['FrO.', '/GIS', '-', '.'], true)) {
                $result[] = $text;
            }
        }

        return $result;
    }

    private function decodeText(string $block): string
    {
        preg_match_all('/\(((?:\\.|[^\\)])*)\)/s', $block, $parts);
        $text = '';

        foreach ($parts[1] as $part) {
            $part = preg_replace_callback('/\\\\([0-7]{1,3})/', fn (array $match): string => chr(octdec($match[1])), $part) ?? $part;
            $text .= str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $part);
        }

        return trim($text);
    }

    /**
     * @param  array<int, string>  $lines
     * @return array<int, array{sort_order: int, title: string, description: ?string, status: string}>
     */
    private function buildItems(array $lines): array
    {
        $items = [];
        $current = null;
        $mode = null;

        foreach ($lines as $line) {
            if (preg_match('/^(\d+\.\d+\.\d+)\s*(.*)$/', $line, $match)) {
                if ($current !== null) {
                    $items[] = $this->finishItem($current);
                }

                $title = trim($match[1].' '.$match[2]);
                $title = preg_split('/\s+Parameter\b/i', $title, 2)[0];
                $title = preg_split('/\s+\d+\.\s*Tersedia\b/i', $title, 2)[0];

                $current = [
                    'sort_order' => count($items),
                    'title' => trim($title),
                    'description' => [],
                    'note' => [],
                ];
                $mode = 'title';

                continue;
            }

            if ($current === null) {
                continue;
            }

            if (preg_match('/^Parameter\b/i', $line)) {
                $mode = 'description';

                continue;
            }

            if (preg_match('/^\d+\.\s*Tersedia\b/i', $line)) {
                $mode = 'description';
                $current['description'][] = $line;

                continue;
            }

            if (preg_match('/^Hasil Audit\s*:?/i', $line)) {
                $mode = 'note';

                continue;
            }

            if (preg_match('/^Status\s*:/i', $line)) {
                $mode = null;

                continue;
            }

            if ($mode === 'title') {
                $current['title'] .= ' '.$line;
            } elseif ($mode === 'description') {
                $current['description'][] = $line;
            } elseif ($mode === 'note') {
                $current['note'][] = $line;
            }
        }

        if ($current !== null) {
            $items[] = $this->finishItem($current);
        }

        return array_values(array_filter($items, fn (array $item): bool => strlen($item['title']) > 8));
    }

    /**
     * @param  array{sort_order: int, title: string, description: array<int, string>, note: array<int, string>}  $item
     * @return array{sort_order: int, title: string, description: ?string, status: string}
     */
    private function finishItem(array $item): array
    {
        return [
            'sort_order' => $item['sort_order'],
            'title' => trim($item['title']),
            'description' => $item['description'] === [] ? null : implode("\n", $item['description']),
            'status' => 'pending',
        ];
    }
}

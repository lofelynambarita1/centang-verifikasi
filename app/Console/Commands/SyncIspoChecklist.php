<?php

namespace App\Console\Commands;

use App\Models\Audit;
use App\Support\IspoChecklist;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('audit:sync-checklist')]
#[Description('Samakan checklist ISPO (7 prinsip) untuk semua audit agar tampilannya konsisten')]
class SyncIspoChecklist extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $canonical = IspoChecklist::items();

        if ($canonical === []) {
            $this->error('Checklist acuan ISPO (7 prinsip) belum tersedia di aplikasi.');

            return self::FAILURE;
        }

        $synced = 0;

        foreach (Audit::with('items')->get() as $audit) {
            if ($audit->items->count() === count($canonical)) {
                continue;
            }

            IspoChecklist::replaceItems($audit);
            $synced++;
        }

        $this->info(sprintf('%d audit disamakan dengan checklist %d indikator.', $synced, count($canonical)));

        return self::SUCCESS;
    }
}

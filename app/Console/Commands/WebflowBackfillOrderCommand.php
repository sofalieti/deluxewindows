<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\WebflowCollectionRegistry;
use App\Support\WebflowItemOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WebflowBackfillOrderCommand extends Command
{
    protected $signature = 'webflow:backfill-order
                            {--force : Renumber every item 1..N by current order/id even when order already exists}
                            {--dry-run : Show what would change without writing}';

    protected $description = 'Backfill field_data.order (and wf_order when present) for all Webflow CMS tables';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');
        $totalUpdated = 0;

        foreach (WebflowCollectionRegistry::all() as $meta) {
            $table = (string) ($meta['table'] ?? '');
            $slug = (string) ($meta['slug'] ?? '');
            if ($table === '' || ! Schema::hasTable($table)) {
                continue;
            }

            $rows = DB::table($table)->orderBy('id')->get();
            if ($rows->isEmpty()) {
                continue;
            }

            $needsWork = $force;
            if (! $force) {
                foreach ($rows as $row) {
                    $fieldData = is_string($row->field_data ?? null)
                        ? (json_decode((string) $row->field_data, true) ?: [])
                        : (is_array($row->field_data ?? null) ? $row->field_data : []);
                    if (! is_numeric($fieldData['order'] ?? null)) {
                        $needsWork = true;
                        break;
                    }
                }
            }

            if (! $needsWork) {
                $this->line("{$slug}: ok (all have order)");

                continue;
            }

            $sorted = $rows->sortBy([
                function ($row) {
                    $fieldData = is_string($row->field_data ?? null)
                        ? (json_decode((string) $row->field_data, true) ?: [])
                        : (is_array($row->field_data ?? null) ? $row->field_data : []);

                    return is_numeric($fieldData['order'] ?? null)
                        ? (int) $fieldData['order']
                        : WebflowItemOrder::MISSING;
                },
                fn ($row) => (int) $row->id,
            ])->values();

            $ids = $sorted->map(fn ($row) => (int) $row->id)->all();

            if ($dryRun) {
                $this->info("{$slug}: would renumber {$sorted->count()} item(s)");
                $totalUpdated += $sorted->count();

                continue;
            }

            $updated = WebflowItemOrder::saveOrder($table, $ids);
            $totalUpdated += $updated;
            $this->info("{$slug}: updated {$updated} item(s)");
        }

        $this->info($dryRun
            ? "Dry run complete. Would touch {$totalUpdated} row(s)."
            : "Backfill complete. Updated {$totalUpdated} row(s).");

        return self::SUCCESS;
    }
}

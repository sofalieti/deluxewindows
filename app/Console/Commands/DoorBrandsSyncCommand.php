<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DoorBrand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DoorBrandsSyncCommand extends Command
{
    protected $signature = 'door-brands:sync
                            {--file= : Path to the JSON source file (default: database/data/door-brands.json)}
                            {--prune : Delete door_brands rows whose slug is not present in the source file}';

    protected $description = 'Sync door-brand content (description + FAQ) from a JSON file into the door_brands table';

    public function handle(): int
    {
        $path = (string) ($this->option('file') ?: database_path('data/door-brands.json'));

        if (! File::exists($path)) {
            $this->error("Source file not found: {$path}");

            return self::FAILURE;
        }

        $decoded = json_decode(File::get($path), true);

        if (! is_array($decoded)) {
            $this->error('Source file is not a valid JSON array.');

            return self::FAILURE;
        }

        $seenSlugs = [];
        $created = 0;
        $updated = 0;

        foreach ($decoded as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $slug = strtolower(trim((string) ($entry['slug'] ?? '')));
            if ($slug === '') {
                $this->warn('Skipping entry without a slug.');

                continue;
            }

            $seenSlugs[] = $slug;

            $faq = $entry['faq'] ?? [];
            if (! is_array($faq)) {
                $faq = [];
            }

            $attributes = [
                'name' => isset($entry['name']) ? (string) $entry['name'] : null,
                'description' => isset($entry['description']) ? (string) $entry['description'] : null,
                'doors_title' => isset($entry['doors_title']) ? (string) $entry['doors_title'] : null,
                'faq' => array_values($faq),
            ];

            $model = DoorBrand::query()->firstOrNew(['slug' => $slug]);
            $existed = $model->exists;
            $model->fill($attributes);
            $model->save();

            $existed ? $updated++ : $created++;

            $this->line(($existed ? 'Updated' : 'Created').": {$slug}");
        }

        if ($this->option('prune')) {
            $deleted = DoorBrand::query()
                ->when($seenSlugs !== [], fn ($q) => $q->whereNotIn('slug', $seenSlugs))
                ->delete();
            if ($deleted > 0) {
                $this->warn("Pruned {$deleted} door-brand row(s) not present in the source file.");
            }
        }

        $this->info("Door brands synced. Created: {$created}, updated: {$updated}.");

        return self::SUCCESS;
    }
}

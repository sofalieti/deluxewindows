<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DoorBrand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DoorBrandsExportCommand extends Command
{
    protected $signature = 'door-brands:export
                            {--file= : Destination JSON file (default: database/data/door-brands.json)}';

    protected $description = 'Export door-brand descriptions from the database into the JSON file';

    public function handle(): int
    {
        $path = (string) ($this->option('file') ?: database_path('data/door-brands.json'));

        $data = DoorBrand::query()
            ->orderBy('slug')
            ->get()
            ->map(function (DoorBrand $item): array {
                return [
                    'slug' => (string) $item->slug,
                    'name' => (string) ($item->name ?? ''),
                    'doors_title' => (string) ($item->doors_title ?? ''),
                    'description' => (string) ($item->description ?? ''),
                ];
            })
            ->values()
            ->all();

        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($json === false) {
            $this->error('Failed to encode door brands to JSON.');

            return self::FAILURE;
        }

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $json.PHP_EOL);

        $this->info('Exported '.count($data).' door brand(s) to '.$path);

        return self::SUCCESS;
    }
}

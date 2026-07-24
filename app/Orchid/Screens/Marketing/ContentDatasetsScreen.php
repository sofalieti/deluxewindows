<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Marketing;

use App\Services\ContentDatasetService;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class ContentDatasetsScreen extends Screen
{
    public function query(): iterable
    {
        return [
            'dataset' => app(ContentDatasetService::class)->status(),
        ];
    }

    public function name(): ?string
    {
        return 'Content datasets';
    }

    public function description(): ?string
    {
        return 'Export or import site content (text/data only — image fields in the database are never changed).';
    }

    public function permission(): ?iterable
    {
        return [
            'platform.marketing',
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make('Export all to files')
                ->method('exportAll')
                ->confirm('Export all current database content to the project dataset files?'),

            Button::make('Import all from files')
                ->method('importAll')
                ->confirm('Import all dataset files into the database? Existing matching records will be updated; other records will not be deleted.'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::view('admin.content-datasets.screen'),
        ];
    }

    public function exportAll(ContentDatasetService $datasets)
    {
        try {
            $result = $datasets->exportAll();
            Toast::info($this->summary('Export complete', $result));
        } catch (\Throwable $e) {
            report($e);
            Toast::error('Content export failed: '.$e->getMessage());
        }

        return redirect()->route('platform.content-datasets');
    }

    public function importAll(ContentDatasetService $datasets)
    {
        try {
            $result = $datasets->importAll();
            Toast::info($this->summary('Import complete', $result));
        } catch (\Throwable $e) {
            report($e);
            Toast::error('Content import failed: '.$e->getMessage());
        }

        return redirect()->route('platform.content-datasets');
    }

    /**
     * @param array<string, int|string> $result
     */
    private function summary(string $prefix, array $result): string
    {
        return sprintf(
            '%s: %d Webflow collections (%d records), %d door brands, %d promotion controls, %d page metadata files.',
            $prefix,
            (int) ($result['webflow_collections'] ?? 0),
            (int) ($result['webflow_records'] ?? 0),
            (int) ($result['door_brands'] ?? 0),
            (int) ($result['promotion_controls'] ?? 0),
            (int) ($result['page_metadata'] ?? 0),
        );
    }
}

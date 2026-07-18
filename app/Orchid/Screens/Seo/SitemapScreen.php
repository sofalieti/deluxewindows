<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Seo;

use App\Services\SitemapGeneratorService;
use Illuminate\Support\Facades\File;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class SitemapScreen extends Screen
{
    public function query(): iterable
    {
        $path = public_path('sitemap.xml');
        $exists = File::exists($path);
        $contents = $exists ? (string) File::get($path) : '';

        return [
            'sitemap' => [
                'exists' => $exists,
                'url' => url('/sitemap.xml'),
                'path' => $path,
                'url_count' => substr_count($contents, '<url>'),
                'size' => $exists ? File::size($path) : 0,
                'updated_at' => $exists
                    ? date('Y-m-d H:i:s', File::lastModified($path))
                    : null,
            ],
        ];
    }

    public function name(): ?string
    {
        return 'Sitemap.xml';
    }

    public function description(): ?string
    {
        return 'Build the sitemap from live public pages and published CMS records.';
    }

    public function commandBar(): iterable
    {
        return [
            Button::make('Generate and save sitemap.xml')
                ->icon('bs.file-earmark-code')
                ->method('generate')
                ->confirm('Rebuild public/sitemap.xml from the current live pages?'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::view('admin.sitemap.screen'),
        ];
    }

    public function generate(SitemapGeneratorService $generator)
    {
        try {
            $result = $generator->generate();
            Toast::info(sprintf(
                'Sitemap saved successfully: %d URLs, %s.',
                $result['count'],
                $this->formatBytes($result['bytes'])
            ));
        } catch (\Throwable $e) {
            report($e);
            Toast::error('Sitemap could not be generated: '.$e->getMessage());
        }

        return redirect()->route('platform.sitemap');
    }

    private function formatBytes(int $bytes): string
    {
        return $bytes >= 1024
            ? number_format($bytes / 1024, 1).' KB'
            : $bytes.' bytes';
    }
}

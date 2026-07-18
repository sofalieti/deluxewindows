<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Webflow\BlogWebflowItem;
use App\Models\Webflow\BrandCollectionsWebflowItem;
use App\Models\Webflow\BrandsWebflowItem;
use App\Models\Webflow\CountyHubPagesWebflowItem;
use App\Models\Webflow\DoorsWebflowItem;
use App\Models\Webflow\DoorTypesWebflowItem;
use App\Models\Webflow\WindowReplacementWebflowItem;
use App\Models\Webflow\WindowsWebflowItem;
use App\Models\Webflow\WindowTypeWebflowItem;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class SitemapGeneratorService
{
    /** @var array<string, array{loc: string, lastmod: string|null}> */
    private array $urls = [];

    /**
     * @return array{count: int, bytes: int, path: string, generated_at: string}
     */
    public function generate(): array
    {
        $this->urls = [];
        $this->addStaticPages();
        $this->addWindows();
        $this->addDoors();
        $this->addBrands();
        $this->addWindowTypes();
        $this->addDoorTypes();
        $this->addBrandCollections();
        $this->addBlogPosts();
        $this->addCountyHubs();
        $this->addServiceAreas();

        $xml = $this->buildXml();
        $path = public_path('sitemap.xml');
        File::put($path, $xml, true);

        return [
            'count' => count($this->urls),
            'bytes' => strlen($xml),
            'path' => $path,
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    private function addStaticPages(): void
    {
        foreach ([
            '/',
            '/windows',
            '/doors',
            '/brand',
            '/blog',
            '/gallery',
            '/glossary',
            '/faq',
            '/testimonials',
            '/financing',
            '/about',
            '/contacts',
            '/special-offers',
        ] as $path) {
            $this->add($path);
        }
    }

    private function addWindows(): void
    {
        foreach ($this->published(WindowsWebflowItem::class) as $item) {
            $fieldData = $this->fieldData($item);
            if (($fieldData['hide'] ?? false) === true
                || ($fieldData['parent-collection'] ?? '') !== 'Windows') {
                continue;
            }

            $this->addModelSlug('/windows/', $item, ['slug']);
        }
    }

    private function addDoors(): void
    {
        foreach ($this->published(DoorsWebflowItem::class) as $item) {
            if (($this->fieldData($item)['hide'] ?? false) === true) {
                continue;
            }

            $this->addModelSlug('/doors/', $item, ['slug']);
        }
    }

    private function addBrands(): void
    {
        foreach ($this->published(BrandsWebflowItem::class) as $item) {
            $this->addModelSlug('/brands/', $item, ['slug']);
            $this->addModelSlug('/door-brands/', $item, ['slug']);
        }
    }

    private function addWindowTypes(): void
    {
        foreach ($this->published(WindowTypeWebflowItem::class) as $item) {
            if ($item->webflowReference('property-listing---agent') === null) {
                continue;
            }

            $this->addModelSlug('/window-type/', $item, ['slug']);
        }
    }

    private function addDoorTypes(): void
    {
        foreach ($this->published(DoorTypesWebflowItem::class) as $item) {
            if ($item->webflowReference('property-listing---agent') === null) {
                continue;
            }

            $this->addModelSlug('/door-types/', $item, ['slug']);
        }
    }

    private function addBrandCollections(): void
    {
        foreach ($this->published(BrandCollectionsWebflowItem::class) as $item) {
            $this->addModelSlug('/brand-collections/', $item, ['slug']);
        }
    }

    private function addBlogPosts(): void
    {
        foreach ($this->published(BlogWebflowItem::class) as $item) {
            $this->addModelSlug('/blog/', $item, ['slug']);
        }
    }

    private function addCountyHubs(): void
    {
        foreach ($this->published(CountyHubPagesWebflowItem::class) as $item) {
            $this->addModelSlug('/county-hub-pages/', $item, ['county-slug', 'slug']);
        }
    }

    private function addServiceAreas(): void
    {
        foreach ($this->published(WindowReplacementWebflowItem::class) as $item) {
            $this->addModelSlug('/window-replacement/', $item, ['city-slug', 'slug']);
        }
    }

    /**
     * @param class-string<Model> $modelClass
     * @return EloquentCollection<int, Model>
     */
    private function published(string $modelClass): EloquentCollection
    {
        return $modelClass::query()
            ->where('is_archived', false)
            ->where('is_draft', false)
            ->get();
    }

    /**
     * @param list<string> $slugFields
     */
    private function addModelSlug(string $prefix, Model $item, array $slugFields): void
    {
        $fieldData = $this->fieldData($item);
        foreach ($slugFields as $field) {
            $slug = strtolower(trim((string) ($fieldData[$field] ?? '')));
            if ($slug !== '') {
                $this->add($prefix.$slug, $item);

                return;
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldData(Model $item): array
    {
        $fieldData = $item->getAttribute('field_data');

        return is_array($fieldData) ? $fieldData : [];
    }

    private function add(string $path, ?Model $item = null): void
    {
        $normalizedPath = $path === '/' ? '/' : '/'.ltrim($path, '/');
        $excludedPaths = (array) config('services.sitemap.excluded_paths', []);
        if (in_array($normalizedPath, $excludedPaths, true)) {
            return;
        }

        $lastModified = $item?->getAttribute('webflow_updated_on');
        if (is_string($lastModified) && $lastModified !== '') {
            try {
                $lastModified = Carbon::parse($lastModified);
            } catch (\Throwable) {
                $lastModified = null;
            }
        }
        if (! $lastModified instanceof \DateTimeInterface) {
            $lastModified = $item?->getAttribute('updated_at');
        }

        $this->urls[$normalizedPath] = [
            'loc' => $this->baseUrl().($normalizedPath === '/' ? '' : $normalizedPath),
            'lastmod' => $lastModified instanceof \DateTimeInterface
                ? $lastModified->format('Y-m-d')
                : null,
        ];
    }

    private function baseUrl(): string
    {
        return rtrim((string) config(
            'services.sitemap.base_url',
            'https://www.deluxewindows.com'
        ), '/');
    }

    private function buildXml(): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($this->urls as $url) {
            $lines[] = '    <url>';
            $lines[] = '        <loc>'.$this->escape($url['loc']).'</loc>';
            if ($url['lastmod'] !== null) {
                $lines[] = '        <lastmod>'.$url['lastmod'].'</lastmod>';
            }
            $lines[] = '    </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines)."\n";
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}

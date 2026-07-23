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
    /** @var array<string, array{loc: string, lastmod: string}> */
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

        $this->urls[$normalizedPath] = [
            'loc' => $this->baseUrl().($normalizedPath === '/' ? '' : $normalizedPath),
            'lastmod' => $this->resolveLastmod($item),
        ];
    }

    /**
     * lastmod is never older than “today” (America/Los_Angeles).
     * When a CMS record exists, use the latest of webflow_updated_on / updated_at.
     */
    private function resolveLastmod(?Model $item = null): string
    {
        $today = Carbon::now('America/Los_Angeles')->startOfDay();
        $candidates = [];

        foreach (['webflow_updated_on', 'updated_at'] as $attribute) {
            $value = $item?->getAttribute($attribute);
            $parsed = $this->parseDate($value);
            if ($parsed !== null) {
                $candidates[] = $parsed->timezone('America/Los_Angeles')->startOfDay();
            }
        }

        $resolved = $today;
        foreach ($candidates as $candidate) {
            if ($candidate->gt($resolved)) {
                $resolved = $candidate;
            }
        }

        return $resolved->format('Y-m-d');
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance(\DateTimeImmutable::createFromInterface($value));
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
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
            $lines[] = '        <lastmod>'.$url['lastmod'].'</lastmod>';
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

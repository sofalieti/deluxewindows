<?php

declare(strict_types=1);

namespace App\Orchid\Screens\DoorBrands;

use App\Models\DoorBrand;
use App\Models\Webflow\BrandsWebflowItem;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class DoorBrandListScreen extends Screen
{
    public function query(): iterable
    {
        $content = DoorBrand::query()->get()->keyBy('slug');

        $rows = BrandsWebflowItem::query()
            ->where('is_archived', false)
            ->where('is_draft', false)
            ->get()
            ->map(function (BrandsWebflowItem $brand) use ($content): ?array {
                $fd = is_array($brand->field_data) ? $brand->field_data : [];
                $slug = trim((string) ($fd['slug'] ?? ''));
                $name = trim((string) ($fd['name'] ?? ''));
                if ($slug === '' || $name === '') {
                    return null;
                }

                /** @var DoorBrand|null $doorBrand */
                $doorBrand = $content->get($slug);

                return [
                    'slug' => $slug,
                    'name' => $name,
                    'has_content' => $doorBrand && trim((string) $doorBrand->description) !== '',
                    'updated_at' => $doorBrand?->updated_at,
                ];
            })
            ->filter()
            ->sortBy('name')
            ->values();

        return [
            'brands' => $rows,
        ];
    }

    public function name(): ?string
    {
        return 'Door Brands';
    }

    public function description(): ?string
    {
        return 'Door-specific descriptions for /door-brands/{slug}. SEO and FAQs are managed in page metadata files.';
    }

    public function layout(): iterable
    {
        return [
            Layout::table('brands', [
                TD::make('name', 'Brand')
                    ->render(fn (array $row) => e($row['name'])),

                TD::make('has_content', 'Door description')
                    ->render(fn (array $row) => $row['has_content']
                        ? '<span class="text-success">Custom</span>'
                        : '<span class="text-muted">Auto (default)</span>'),

                TD::make('updated_at', 'Updated')
                    ->render(fn (array $row) => $row['updated_at']
                        ? e($row['updated_at']->format('Y-m-d H:i'))
                        : '<span class="text-muted">-</span>'),

                TD::make('actions', '')
                    ->align(TD::ALIGN_RIGHT)
                    ->render(function (array $row): string {
                        $editUrl = route('platform.door-brands.edit', ['slug' => $row['slug']]);
                        $viewUrl = url('/door-brands/'.$row['slug']);

                        return '<a href="'.e($editUrl).'" class="btn btn-sm btn-link">Edit</a>'
                            .'<a href="'.e($viewUrl).'" target="_blank" rel="noopener" class="btn btn-sm btn-link">View page</a>';
                    }),
            ]),
        ];
    }
}

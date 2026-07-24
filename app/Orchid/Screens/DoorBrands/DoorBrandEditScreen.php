<?php

declare(strict_types=1);

namespace App\Orchid\Screens\DoorBrands;

use App\Models\DoorBrand;
use App\Models\Webflow\BrandsWebflowItem;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Quill;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class DoorBrandEditScreen extends Screen
{
    protected string $slug = '';

    protected string $brandName = '';

    public function query(string $slug): iterable
    {
        $slug = strtolower(trim($slug));

        $brand = BrandsWebflowItem::query()
            ->where('field_data->slug', $slug)
            ->orWhere('webflow_item_id', $slug)
            ->orderByDesc('id')
            ->first();

        abort_if(! $brand, 404);

        $fd = is_array($brand->field_data) ? $brand->field_data : [];
        $this->slug = (string) ($fd['slug'] ?? $slug);
        $this->brandName = (string) ($fd['name'] ?? 'Brand');

        $doorBrand = DoorBrand::query()->where('slug', $this->slug)->first();

        return [
            'doorBrand' => [
                'slug' => $this->slug,
                'name' => $doorBrand?->name ?: $this->brandName,
                'description' => $doorBrand?->description ?? '',
                'doors_title' => $doorBrand?->doors_title ?? '',
            ],
        ];
    }

    public function name(): ?string
    {
        return 'Edit Door Brand: '.$this->brandName;
    }

    public function description(): ?string
    {
        return 'This content is shown on /door-brands/'.$this->slug.'. Leave the description empty to fall back to an auto-generated one.';
    }

    public function permission(): ?iterable
    {
        return [
            'platform.webflow.manage',
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('Back to list')
                ->icon('bs.arrow-left')
                ->route('platform.door-brands'),

            Button::make('Save')
                ->icon('bs.check-circle')
                ->method('save'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('doorBrand.name')
                    ->title('Brand name')
                    ->help('Used in headings and default copy.'),

                Input::make('doorBrand.slug')
                    ->title('Slug')
                    ->readonly()
                    ->help('Matches the brand slug. The page URL is /door-brands/{slug}.'),

                Input::make('doorBrand.doors_title')
                    ->title('Door types section heading')
                    ->placeholder('Explore '.$this->brandName."'s Door Types")
                    ->help('Optional. Heading above the door types grid. Leave empty to use the default.'),

                Quill::make('doorBrand.description')
                    ->title('Door description (HTML)')
                    ->help('Shown at the top of the door page. Leave empty to auto-generate a default description.'),
            ]),
        ];
    }

    public function save(string $slug, Request $request)
    {
        $slug = strtolower(trim($slug));

        $brand = BrandsWebflowItem::query()
            ->where('field_data->slug', $slug)
            ->orWhere('webflow_item_id', $slug)
            ->orderByDesc('id')
            ->first();

        abort_if(! $brand, 404);

        $fd = is_array($brand->field_data) ? $brand->field_data : [];
        $resolvedSlug = (string) ($fd['slug'] ?? $slug);

        $data = $request->input('doorBrand', []);
        $data = is_array($data) ? $data : [];

        $doorBrand = DoorBrand::query()->firstOrNew(['slug' => $resolvedSlug]);
        $doorBrand->name = trim((string) ($data['name'] ?? '')) ?: ($fd['name'] ?? null);
        $doorBrand->description = trim((string) ($data['description'] ?? '')) ?: null;
        $doorBrand->doors_title = trim((string) ($data['doors_title'] ?? '')) ?: null;
        $doorBrand->save();

        Toast::info('Door brand content saved.');

        return redirect()->route('platform.door-brands');
    }
}

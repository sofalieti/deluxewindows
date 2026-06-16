<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Promotions;

use App\Models\Webflow\GlobalSettingsWebflowItem;
use App\Models\Webflow\BrandCollectionsWebflowItem;
use App\Models\Webflow\BrandsWebflowItem;
use App\Models\Webflow\WindowsWebflowItem;
use App\Services\PromotionControlService;
use App\Services\PromotionSettingsService;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class PromotionsScreen extends Screen
{
    public function __construct(
        private readonly PromotionControlService $controls,
        private readonly PromotionSettingsService $legacyPromotionSettings,
    ) {
    }

    public function query(): iterable
    {
        $control = $this->controls->get();
        $windowTypeMap = is_array($control->window_type_prices) ? $control->window_type_prices : [];
        $seriesMap = is_array($control->series_prices) ? $control->series_prices : [];
        $brandMap = is_array($control->brand_prices) ? $control->brand_prices : [];

        $windowTypes = $this->windowTypeItems();
        $series = $this->seriesItems();
        $brands = $this->brandItems();

        $windowTypePrices = [];
        foreach ($windowTypes as $item) {
            $slug = $item['slug'];
            $windowTypePrices[$slug] = [
                'base' => (string) ($windowTypeMap[$slug]['base'] ?? ''),
                'final' => (string) ($windowTypeMap[$slug]['final'] ?? ''),
            ];
        }

        $seriesPrices = [];
        foreach ($series as $item) {
            $slug = $item['slug'];
            $seriesPrices[$slug] = [
                'base' => (string) ($seriesMap[$slug]['base'] ?? ''),
                'final' => (string) ($seriesMap[$slug]['final'] ?? ''),
            ];
        }

        $brandPrices = [];
        foreach ($brands as $item) {
            $slug = $item['slug'];
            $brandPrices[$slug] = [
                'base' => (string) ($brandMap[$slug]['base'] ?? ''),
                'final' => (string) ($brandMap[$slug]['final'] ?? ''),
            ];
        }

        return [
            'promotions' => [
                'global_promotion_name' => (string) ($control->global_promotion_name ?? ''),
                'global_discount_percent' => $control->global_discount_percent,
                'global_end_date' => optional($control->global_end_date)->format('Y-m-d') ?? '',
                'window_type_prices' => $windowTypePrices,
                'series_prices' => $seriesPrices,
                'brand_prices' => $brandPrices,
            ],
        ];
    }

    public function name(): ?string
    {
        return 'Promotions Control Center';
    }

    public function description(): ?string
    {
        return 'Manage global discount, end date, and per-page pricing in one place.';
    }

    public function commandBar(): iterable
    {
        return [
            Button::make('Save promotions')
                ->icon('bs.check-circle')
                ->method('save'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::tabs([
                'Global' => Layout::rows([
                    Input::make('promotions.global_promotion_name')
                        ->title('Global Promotion Title')
                        ->required()
                        ->help('Shown on discount cards (brand/window pages).'),
                    Input::make('promotions.global_discount_percent')
                        ->title('Global Discount Percent')
                        ->type('number')
                        ->min(0)
                        ->max(95)
                        ->required()
                        ->help('Used anywhere the site displays percentage discount labels.'),
                    Input::make('promotions.global_end_date')
                        ->title('Global Offer End Date')
                        ->type('date')
                        ->required()
                        ->help('Single end date shown on all discount-related sections.'),
                ]),
                'Window Types' => Layout::rows($this->pricingRows('window_type_prices', $this->windowTypeItems(), 'Price per Windows item')),
                'Series' => Layout::rows($this->pricingRows('series_prices', $this->seriesItems(), 'Price per series')),
                'Brands' => Layout::rows($this->pricingRows('brand_prices', $this->brandItems(), 'Brand override price. Leave empty to inherit from linked Windows type.')),
            ]),
        ];
    }

    public function save(Request $request)
    {
        $data = $request->input('promotions', []);
        $promotionName = trim((string) ($data['global_promotion_name'] ?? ''));
        $discountPercent = (int) ($data['global_discount_percent'] ?? 40);
        $endDate = trim((string) ($data['global_end_date'] ?? ''));

        $windowTypePrices = $this->normalizePricingMap($data['window_type_prices'] ?? []);
        $seriesPrices = $this->normalizePricingMap($data['series_prices'] ?? []);
        $brandPrices = $this->normalizePricingMap($data['brand_prices'] ?? []);

        $control = $this->controls->get();
        $control->global_promotion_name = $promotionName;
        $control->global_discount_percent = max(0, min(95, $discountPercent));
        $control->global_end_date = $endDate === '' ? null : $endDate;
        $control->window_type_prices = $windowTypePrices;
        $control->series_prices = $seriesPrices;
        $control->brand_prices = $brandPrices;
        $control->save();

        $this->syncLegacyGlobalSettings(
            $control->global_end_date?->format('n/j/y'),
            $promotionName
        );

        $this->controls->forgetCache();
        $this->legacyPromotionSettings->forgetCache();

        Toast::info('Promotions updated.');

        return redirect()->route('platform.promotions');
    }

    /**
     * @return array<string, array{base: string, final: string}>
     */
    private function normalizePricingMap(mixed $input): array
    {
        if (! is_array($input)) {
            return [];
        }

        $normalized = [];
        foreach ($input as $slug => $values) {
            if (! is_string($slug) || ! is_array($values)) {
                continue;
            }
            $base = trim((string) ($values['base'] ?? ''));
            $final = trim((string) ($values['final'] ?? ''));
            if ($base === '' || $final === '') {
                continue;
            }
            $normalized[$slug] = ['base' => $base, 'final' => $final];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array{slug: string, name: string}>  $items
     * @return array<int, \Orchid\Screen\Field>
     */
    private function pricingRows(string $scope, array $items, string $help): array
    {
        $rows = [];

        foreach ($items as $item) {
            $slug = $item['slug'];
            $name = $item['name'];

            $rows[] = Group::make([
                Input::make("meta.{$scope}.{$slug}.name")
                    ->title('Name')
                    ->readonly()
                    ->value("{$name} ({$slug})"),
                Input::make("promotions.{$scope}.{$slug}.base")
                    ->title('Base')
                    ->placeholder('e.g. 1199')
                    ->help($help),
                Input::make("promotions.{$scope}.{$slug}.final")
                    ->title('Final')
                    ->placeholder('e.g. 799'),
            ]);
        }

        if ($rows === []) {
            $rows[] = Input::make("promotions.{$scope}.__empty")
                ->title('No items found')
                ->readonly()
                ->value('No records available for this tab.');
        }

        return $rows;
    }

    /**
     * @return array<int, array{slug: string, name: string}>
     */
    private function windowTypeItems(): array
    {
        return WindowsWebflowItem::query()
            ->where('is_archived', false)
            ->where('is_draft', false)
            ->orderBy('id')
            ->get()
            ->map(function (WindowsWebflowItem $item): ?array {
                $fd = is_array($item->field_data) ? $item->field_data : [];
                $slug = trim((string) ($fd['slug'] ?? ''));
                $name = trim((string) ($fd['name'] ?? ''));
                if ($slug === '' || $name === '') {
                    return null;
                }
                if (($fd['parent-collection'] ?? '') !== 'Windows') {
                    return null;
                }

                return ['slug' => $slug, 'name' => $name];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{slug: string, name: string}>
     */
    private function seriesItems(): array
    {
        return BrandCollectionsWebflowItem::query()
            ->where('is_archived', false)
            ->where('is_draft', false)
            ->orderBy('id')
            ->get()
            ->map(function (BrandCollectionsWebflowItem $item): ?array {
                $fd = is_array($item->field_data) ? $item->field_data : [];
                $slug = trim((string) ($fd['slug'] ?? ''));
                $name = trim((string) ($fd['name'] ?? ''));
                if ($slug === '' || $name === '') {
                    return null;
                }

                return ['slug' => $slug, 'name' => $name];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{slug: string, name: string}>
     */
    private function brandItems(): array
    {
        return BrandsWebflowItem::query()
            ->where('is_archived', false)
            ->where('is_draft', false)
            ->orderBy('id')
            ->get()
            ->map(function (BrandsWebflowItem $item): ?array {
                $fd = is_array($item->field_data) ? $item->field_data : [];
                $slug = trim((string) ($fd['slug'] ?? ''));
                $name = trim((string) ($fd['name'] ?? ''));
                if ($slug === '' || $name === '') {
                    return null;
                }

                return ['slug' => $slug, 'name' => $name];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function syncLegacyGlobalSettings(?string $endDate, string $promotionName): void
    {
        $item = GlobalSettingsWebflowItem::query()
            ->where('is_archived', false)
            ->where('is_draft', false)
            ->where(function ($query) {
                $query->where('field_data->slug', 'default')
                    ->orWhereNull('field_data->slug');
            })
            ->orderBy('id')
            ->first();

        if (! $item) {
            return;
        }

        $fieldData = is_array($item->field_data) ? $item->field_data : [];
        $fieldData['end-date'] = $endDate ?? '';
        $fieldData['promotion-name'] = $promotionName;
        $item->field_data = $fieldData;
        $item->wf_end_date = $endDate;
        $item->wf_promotion_name = $promotionName;
        $item->save();
    }
}


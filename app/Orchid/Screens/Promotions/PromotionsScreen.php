<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Promotions;

use App\Models\Webflow\GlobalSettingsWebflowItem;
use App\Services\PromotionControlService;
use App\Services\PromotionSettingsService;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;
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

        return [
            'promotions' => [
                'global_discount_percent' => $control->global_discount_percent,
                'global_end_date' => optional($control->global_end_date)->format('Y-m-d') ?? '',
                'window_type_prices_json' => json_encode($control->window_type_prices ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}',
                'series_prices_json' => json_encode($control->series_prices ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}',
                'brand_prices_json' => json_encode($control->brand_prices ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}',
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
                'Window Types & Series' => Layout::rows([
                    TextArea::make('promotions.window_type_prices_json')
                        ->title('Window Type Prices (JSON by slug)')
                        ->rows(8)
                        ->help('Format: {"double-hung":{"base":"1199","final":"799"}}'),
                    TextArea::make('promotions.series_prices_json')
                        ->title('Series Prices (Brand Collections, JSON by slug)')
                        ->rows(8)
                        ->help('Format: {"vinyl-series":{"base":"915","final":"549"}}'),
                ]),
                'Brands' => Layout::rows([
                    TextArea::make('promotions.brand_prices_json')
                        ->title('Brand Prices Overrides (JSON by brand slug)')
                        ->rows(8)
                        ->help('Format: {"milgard":{"base":"1299","final":"899"}}'),
                ]),
            ]),
        ];
    }

    public function save(Request $request)
    {
        $data = $request->input('promotions', []);
        $discountPercent = (int) ($data['global_discount_percent'] ?? 40);
        $endDate = trim((string) ($data['global_end_date'] ?? ''));

        $windowTypePrices = $this->decodePricingMap((string) ($data['window_type_prices_json'] ?? '{}'));
        $seriesPrices = $this->decodePricingMap((string) ($data['series_prices_json'] ?? '{}'));
        $brandPrices = $this->decodePricingMap((string) ($data['brand_prices_json'] ?? '{}'));

        $control = $this->controls->get();
        $control->global_discount_percent = max(0, min(95, $discountPercent));
        $control->global_end_date = $endDate === '' ? null : $endDate;
        $control->window_type_prices = $windowTypePrices;
        $control->series_prices = $seriesPrices;
        $control->brand_prices = $brandPrices;
        $control->save();

        $this->syncLegacyGlobalSettings($control->global_end_date?->format('n/j/y'));

        $this->controls->forgetCache();
        $this->legacyPromotionSettings->forgetCache();

        Toast::info('Promotions updated.');

        return redirect()->route('platform.promotions');
    }

    /**
     * @return array<string, array{base: string, final: string}>
     */
    private function decodePricingMap(string $json): array
    {
        $decoded = json_decode(trim($json), true);
        if (! is_array($decoded)) {
            return [];
        }

        $normalized = [];
        foreach ($decoded as $slug => $values) {
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

    private function syncLegacyGlobalSettings(?string $endDate): void
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
        $item->field_data = $fieldData;
        $item->wf_end_date = $endDate;
        $item->save();
    }
}


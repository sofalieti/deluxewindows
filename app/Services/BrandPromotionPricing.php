<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Webflow\BrandsWebflowItem;
use App\Models\Webflow\DoorsWebflowItem;
use App\Models\Webflow\DoorTypesWebflowItem;
use App\Models\Webflow\WindowsWebflowItem;
use App\Models\Webflow\WindowTypeWebflowItem;

/**
 * Brand / material / type hero pricing — same rules as ClassicSiteController catalog pages.
 *
 * @phpstan-type PricingMap array{base: string, final: string}
 */
final class BrandPromotionPricing
{
    public function __construct(
        private readonly PromotionControlService $controls,
    ) {
    }

    /**
     * Window brand: Promotions brand override → windowmaintype → materials.
     *
     * @return PricingMap|null
     */
    public function forWindowBrand(BrandsWebflowItem $brand, string $brandSlug = ''): ?array
    {
        $slug = $brandSlug !== ''
            ? $brandSlug
            : (string) (is_array($brand->field_data) ? ($brand->field_data['slug'] ?? '') : '');

        $override = $this->controls->brandPricing(
            (string) ($brand->webflow_item_id ?? ''),
            $slug
        );
        if ($override !== null) {
            return $override;
        }

        $mainType = $brand->webflowReference('windowmaintype');
        if ($mainType instanceof WindowsWebflowItem) {
            $inherited = $this->windowTypePricingFromItem($mainType);
            if ($inherited !== null) {
                return $inherited;
            }
        }

        $mainTypeId = trim((string) (is_array($brand->field_data) ? ($brand->field_data['windowmaintype'] ?? '') : ''));
        if ($mainTypeId !== '') {
            $mainTypeRow = WindowsWebflowItem::query()
                ->where('webflow_item_id', $mainTypeId)
                ->orWhere('field_data->slug', $mainTypeId)
                ->first();
            if ($mainTypeRow instanceof WindowsWebflowItem) {
                $inherited = $this->windowTypePricingFromItem($mainTypeRow);
                if ($inherited !== null) {
                    return $inherited;
                }
            }
        }

        foreach ($brand->webflowReferences('materials') as $material) {
            if (! $material instanceof WindowsWebflowItem) {
                continue;
            }
            $inherited = $this->windowTypePricingFromItem($material);
            if ($inherited !== null) {
                return $inherited;
            }
        }

        return null;
    }

    /**
     * Door brand: cheapest priced door linked via doors-brands.
     *
     * @return PricingMap|null
     */
    public function forDoorBrand(BrandsWebflowItem $brand): ?array
    {
        $brandId = trim((string) ($brand->webflow_item_id ?? ''));
        if ($brandId === '') {
            return null;
        }

        $best = null;
        $bestValue = null;

        DoorsWebflowItem::query()
            ->where('is_archived', false)
            ->where('is_draft', false)
            ->get()
            ->each(function (DoorsWebflowItem $door) use ($brandId, &$best, &$bestValue) {
                $fd = is_array($door->field_data) ? $door->field_data : [];
                $brandRefs = $fd['doors-brands'] ?? [];
                if (! is_array($brandRefs) || ! in_array($brandId, $brandRefs, true)) {
                    return;
                }

                $pricing = $this->controls->doorPricing(
                    (string) ($door->webflow_item_id ?? ''),
                    (string) ($fd['slug'] ?? '')
                );
                if ($pricing === null) {
                    return;
                }

                $value = $this->priceToFloat((string) ($pricing['final'] ?? ''));
                if ($value === null) {
                    return;
                }

                if ($bestValue === null || $value < $bestValue) {
                    $bestValue = $value;
                    $best = $pricing;
                }
            });

        return $best;
    }

    /**
     * Resolve pricing for a public Product catalog path (brands, materials, types).
     *
     * @return PricingMap|null
     */
    public function forPath(string $path): ?array
    {
        $path = '/'.trim($path, '/');
        $resolved = null;

        if (preg_match('~^/brands/([a-z0-9-]+)$~', $path, $m)) {
            $brand = $this->findBrand($m[1]);
            $resolved = $brand ? $this->forWindowBrand($brand, $m[1]) : null;
        } elseif (preg_match('~^/door-brands/([a-z0-9-]+)$~', $path, $m)) {
            $brand = $this->findBrand($m[1]);
            $resolved = $brand ? $this->forDoorBrand($brand) : null;
        } elseif (preg_match('~^/windows/([a-z0-9-]+)$~', $path, $m)) {
            $resolved = $this->forWindowMaterialSlug($m[1]);
        } elseif (preg_match('~^/doors/([a-z0-9-]+)$~', $path, $m)) {
            $resolved = $this->forDoorMaterialSlug($m[1]);
        } elseif (preg_match('~^/window-type/([a-z0-9-]+)$~', $path, $m)) {
            $resolved = $this->forWindowTypeSlug($m[1]);
        } elseif (preg_match('~^/door-types/([a-z0-9-]+)$~', $path, $m)) {
            $resolved = $this->forDoorTypeSlug($m[1]);
        } else {
            return null;
        }

        return $resolved ?? $this->defaultCatalogPricing($path);
    }

    /**
     * Schema.org Offer / AggregateOffer from hero pricing map.
     *
     * @param  PricingMap  $pricing
     * @return array<string, mixed>
     */
    public function toSchemaOffer(array $pricing, string $url, string $unitText = 'per window installed'): array
    {
        $low = $this->priceToFloat((string) ($pricing['final'] ?? ''));
        $high = $this->priceToFloat((string) ($pricing['base'] ?? ''));

        if ($low === null) {
            return [];
        }

        $lowStr = number_format($low, 2, '.', '');
        $highStr = $high !== null && $high > $low
            ? number_format($high, 2, '.', '')
            : $lowStr;

        $offer = [
            '@type' => $highStr !== $lowStr ? 'AggregateOffer' : 'Offer',
            'url' => $url,
            'priceCurrency' => 'USD',
            'availability' => 'https://schema.org/InStock',
            'itemCondition' => 'https://schema.org/NewCondition',
        ];

        if ($highStr !== $lowStr) {
            $offer['lowPrice'] = $lowStr;
            $offer['highPrice'] = $highStr;
            $offer['offerCount'] = 1;
        } else {
            $offer['price'] = $lowStr;
        }

        $end = $this->controls->endDate();
        if ($end !== null) {
            $offer['priceValidUntil'] = $end->format('Y-m-d');
        }

        $offer['description'] = 'Starting from $'.rtrim(rtrim($lowStr, '0'), '.').' '.$unitText;

        return $offer;
    }

    public function priceToFloat(string $value): ?float
    {
        $clean = preg_replace('/[^0-9.]/', '', $value);

        return ($clean === '' || $clean === null) ? null : (float) $clean;
    }

    public function isDoorCatalogPath(string $path): bool
    {
        $path = '/'.trim($path, '/');

        return (bool) preg_match('~^/(doors|door-brands|door-types)(/|$)~', $path);
    }

    private function findBrand(string $slug): ?BrandsWebflowItem
    {
        return BrandsWebflowItem::query()
            ->where('field_data->slug', $slug)
            ->orWhere('webflow_item_id', $slug)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return PricingMap|null
     */
    private function forWindowMaterialSlug(string $slug): ?array
    {
        $item = WindowsWebflowItem::query()
            ->where('field_data->slug', $slug)
            ->orWhere('webflow_item_id', $slug)
            ->orderByDesc('id')
            ->first();

        if ($item instanceof WindowsWebflowItem) {
            $pricing = $this->windowTypePricingFromItem($item);
            if ($pricing !== null) {
                return $pricing;
            }
        }

        return $this->controls->windowTypePricing(null, $slug);
    }

    /**
     * @return PricingMap|null
     */
    private function forDoorMaterialSlug(string $slug): ?array
    {
        $item = DoorsWebflowItem::query()
            ->where('field_data->slug', $slug)
            ->orWhere('webflow_item_id', $slug)
            ->orderByDesc('id')
            ->first();

        return $this->controls->doorPricing(
            (string) ($item->webflow_item_id ?? ''),
            $slug
        );
    }

    /**
     * @return PricingMap|null
     */
    private function forWindowTypeSlug(string $slug): ?array
    {
        $windowType = WindowTypeWebflowItem::query()
            ->where('field_data->slug', $slug)
            ->orWhere('webflow_item_id', $slug)
            ->orderByDesc('id')
            ->first();

        if (! $windowType instanceof WindowTypeWebflowItem) {
            return $this->controls->windowTypePricing(null, $slug);
        }

        $fd = is_array($windowType->field_data) ? $windowType->field_data : [];
        $typeSlug = (string) ($fd['slug'] ?? $slug);

        $pricing = $this->controls->windowTypePricing(
            (string) ($windowType->webflow_item_id ?? ''),
            $typeSlug
        );
        if ($pricing !== null) {
            return $pricing;
        }

        $material = $windowType->webflowReference('windor-type-material');
        if ($material instanceof WindowsWebflowItem) {
            return $this->windowTypePricingFromItem($material);
        }

        return null;
    }

    /**
     * Door-type hero price: matching material slug → cheapest door on parent brand.
     *
     * @return PricingMap|null
     */
    private function forDoorTypeSlug(string $slug): ?array
    {
        $normalized = str_replace('aluminium', 'aluminum', $slug);
        foreach (['wood-clad', 'fiberglass', 'aluminum', 'vinyl', 'steel', 'wood'] as $material) {
            if (! str_contains($normalized, $material)) {
                continue;
            }
            $materialSlug = "{$material}-doors";
            $door = DoorsWebflowItem::query()
                ->where('field_data->slug', $materialSlug)
                ->first();
            $pricing = $this->controls->doorPricing(
                (string) ($door->webflow_item_id ?? ''),
                $materialSlug
            );
            if ($pricing !== null) {
                return $pricing;
            }
            break;
        }

        $doorType = DoorTypesWebflowItem::query()
            ->where('field_data->slug', $slug)
            ->orWhere('webflow_item_id', $slug)
            ->orderByDesc('id')
            ->first();

        if (! $doorType instanceof DoorTypesWebflowItem) {
            return null;
        }

        $parentBrand = $doorType->webflowReference('property-listing---agent');
        if ($parentBrand instanceof BrandsWebflowItem) {
            return $this->forDoorBrand($parentBrand);
        }

        return null;
    }

    /**
     * Site-wide promo defaults shown on catalog heroes when no mapped price exists.
     *
     * @return PricingMap|null
     */
    private function defaultCatalogPricing(string $path): ?array
    {
        if (preg_match('~^/(doors|door-brands|door-types)(/|$)~', $path)) {
            return ['base' => '2165', 'final' => '1299'];
        }

        if (preg_match('~^/(windows|brands|window-type)(/|$)~', $path)) {
            return ['base' => '915', 'final' => '549'];
        }

        return null;
    }

    /**
     * @return PricingMap|null
     */
    private function windowTypePricingFromItem(WindowsWebflowItem $item): ?array
    {
        $fd = is_array($item->field_data) ? $item->field_data : [];
        $slug = trim((string) ($fd['slug'] ?? ''));
        if ($slug === '') {
            return null;
        }

        return $this->controls->windowTypePricing(
            (string) ($item->webflow_item_id ?? ''),
            $slug
        );
    }
}

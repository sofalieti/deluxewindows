<?php

namespace App\Http\Controllers;

use App\Models\Webflow\WindowsWebflowItem;

class ClassicSiteController extends Controller
{
    public function home()
    {
        return view('home');
    }

    public function windowBySlug(string $slug)
    {
        $slug = strtolower(trim($slug));

        $window = WindowsWebflowItem::query()
            ->where('field_data->slug', $slug)
            ->orWhere('webflow_item_id', $slug)
            ->orderByDesc('id')
            ->first();

        abort_if(! $window, 404);

        $fieldData = is_array($window->field_data ?? null) ? $window->field_data : [];

        $heroImage = $this->extractImageUrl($fieldData, [
            'property-listing---thumbnail-image-v1',
            'property-listing---featured-image',
        ]);

        $galleryImages = collect(data_get($fieldData, 'property-listing---featured-images', []))
            ->map(fn ($img) => is_array($img) ? ($img['url'] ?? null) : (is_string($img) ? $img : null))
            ->filter()
            ->values();

        // Brand logos — only those referenced on this specific window item
        $brands = $window->webflowReferences('brands')
            ->map(function ($brand) {
                $fd = is_array($brand->field_data) ? $brand->field_data : [];
                $brandSlug = $fd['slug'] ?? '';
                $brandName = $fd['name'] ?? '';
                $logo = $this->extractImageUrl($fd, ['brand-logo', 'logo-svg', 'agent-avatar-photo']);

                return ($brandSlug !== '' && $logo !== null)
                    ? ['name' => $brandName, 'slug' => $brandSlug, 'logo' => $logo]
                    : null;
            })
            ->filter()
            ->values();

        // Other windows for "Discover Different Window Types" section
        $otherWindows = WindowsWebflowItem::query()
            ->where('is_archived', false)
            ->where('is_draft', false)
            ->where('id', '!=', $window->id)
            ->take(6)
            ->get()
            ->map(function ($w) {
                $fd = is_array($w->field_data) ? $w->field_data : [];
                $wSlug = $fd['slug'] ?? '';
                $wName = $fd['name'] ?? '';
                $wImage = $this->extractImageUrl($fd, [
                    'property-listing---featured-image',
                ]);
                $wSummary = $fd['property-listing---summary'] ?? '';

                return $wSlug !== ''
                    ? ['name' => $wName, 'slug' => $wSlug, 'image' => $wImage ?? '', 'summary' => $wSummary]
                    : null;
            })
            ->filter()
            ->values();

        $seoTitle       = $fieldData['seo-title'] ?? ($fieldData['name'] ?? 'Windows');
        $seoDescription = $fieldData['seo-description'] ?? '';
        $ogTitle        = $fieldData['opengraph-title'] ?? $seoTitle;
        $ogDescription  = $fieldData['opengraph-description'] ?? $seoDescription;
        $ogImage        = $fieldData['opengraph-image'] ?? $heroImage ?? '';

        return view('windows.show', [
            'windowFieldData'  => $fieldData,
            'seoTitle'         => $seoTitle,
            'seoDescription'   => $seoDescription,
            'ogTitle'          => $ogTitle,
            'ogDescription'    => $ogDescription,
            'ogImage'          => $ogImage,
            'title'            => $fieldData['name'] ?? 'Window',
            'summary'          => $fieldData['property-listing---summary'] ?? '',
            'aboutHtml'        => $fieldData['property-listing---about'] ?? '',
            'discountHtml'     => $fieldData['discounttext'] ?? '',
            'warrantyHtml'     => $fieldData['warrantytext'] ?? '',
            'heroImage'        => $heroImage ?? '',
            'galleryImages'    => $galleryImages,
            'brands'           => $brands,
            'otherWindows'     => $otherWindows,
        ]);
    }

    private function extractImageUrl(array $fieldData, array $fieldKeys): ?string
    {
        foreach ($fieldKeys as $key) {
            $value = $fieldData[$key] ?? null;
            if (is_array($value) && isset($value['url']) && $value['url'] !== '') {
                return $value['url'];
            }
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}

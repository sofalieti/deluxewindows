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

        $brandTypeItems = $window->webflowReferences('brands-types')
            ->map(function ($item) {
                $fd = is_array($item->field_data) ? $item->field_data : [];
                $name = $fd['name'] ?? '';
                $itemSlug = $fd['slug'] ?? '';
                $image = $this->extractImageUrl($fd, [
                    'property-listing---thumbnail-image-v1',
                    'property-listing---featured-image',
                    'agent---avatar-photo',
                ]);

                return ($name !== '' && $itemSlug !== '')
                    ? ['name' => $name, 'slug' => $itemSlug, 'image' => $image ?? '']
                    : null;
            })
            ->filter()
            ->values();

        $seoTitle       = $fieldData['seo-title'] ?? ($fieldData['name'] ?? 'Windows');
        $seoDescription = $fieldData['seo-description'] ?? '';
        $ogTitle        = $fieldData['opengraph-title'] ?? $seoTitle;
        $ogDescription  = $fieldData['opengraph-description'] ?? $seoDescription;
        $ogImage        = $fieldData['opengraph-image'] ?? $heroImage ?? '';

        return view('classic.windows.show', [
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
            'titleForBrands'   => $fieldData['title-for-brands'] ?? 'Top Window Brands',
            'heroImage'        => $heroImage ?? '',
            'galleryImages'    => $galleryImages,
            'brandTypeItems'   => $brandTypeItems,
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

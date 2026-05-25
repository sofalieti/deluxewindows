<?php

namespace App\Http\Controllers;

use App\Models\Webflow\WindowsWebflowItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ClassicSiteController extends Controller
{
    private const SHARED_CSS_HREF = '/webflow-overrides/classic-shared.css?v=1';

    public function home()
    {
        return $this->renderMirrorView('webflow.mirror.home');
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
        $galleryImages = collect(data_get($fieldData, 'property-listing---featured-images', []))
            ->map(fn ($image) => is_array($image) ? ($image['url'] ?? null) : null)
            ->filter(fn ($url) => is_string($url) && $url !== '')
            ->values();

        $heroImage = $this->extractImageUrl($fieldData, [
            'property-listing---thumbnail-image-v1',
            'property-listing---featured-image',
            'property-listing---thumbnail-image-v2',
            'property-listing---thumbnail-image-v3',
        ]);

        $brands = $this->mapReferenceCards($window->webflowReferences('brands'));
        $brandTypes = $this->mapReferenceCards($window->webflowReferences('brands-types'));

        return view('classic.windows.show', [
            'windowItem' => $window,
            'windowFieldData' => $fieldData,
            'seoTitle' => data_get($fieldData, 'seo-title') ?: data_get($fieldData, 'name', 'Windows'),
            'seoDescription' => data_get($fieldData, 'seo-description'),
            'title' => data_get($fieldData, 'name', 'Window'),
            'shortTitle' => data_get($fieldData, 'short-title'),
            'summary' => data_get($fieldData, 'property-listing---summary'),
            'aboutHtml' => data_get($fieldData, 'property-listing---about'),
            'discountHtml' => data_get($fieldData, 'discounttext'),
            'warrantyHtml' => data_get($fieldData, 'warrantytext'),
            'titleForBrands' => data_get($fieldData, 'title-for-brands'),
            'heroImage' => $heroImage,
            'galleryImages' => $galleryImages,
            'brands' => $brands,
            'brandTypes' => $brandTypes,
        ]);
    }

    private function mapReferenceCards(Collection $items): Collection
    {
        return $items
            ->map(function (Model $item) {
                $fieldData = is_array($item->getAttribute('field_data')) ? $item->getAttribute('field_data') : [];
                $name = data_get($fieldData, 'name');
                $slug = data_get($fieldData, 'slug');
                $image = $this->extractImageUrl($fieldData, [
                    'brand-logo',
                    'logo-svg',
                    'property-listing---thumbnail-image-v1',
                    'property-listing---featured-image',
                    'featured-image',
                    'agent---avatar-photo',
                ]);

                if (! is_string($name) || $name === '' || ! is_string($slug) || $slug === '') {
                    return null;
                }

                return [
                    'name' => $name,
                    'slug' => $slug,
                    'image' => $image,
                ];
            })
            ->filter()
            ->values();
    }

    private function extractImageUrl(array $fieldData, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $fieldData[$key] ?? null;
            if (is_array($value) && is_string($value['url'] ?? null) && $value['url'] !== '') {
                return $value['url'];
            }
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function renderMirrorView(string $viewName, array $data = [])
    {
        abort_if(! view()->exists($viewName), 404);

        $html = view($viewName, $data)->render();
        if (! str_contains($html, '/webflow-overrides/classic-shared.css')) {
            $linkTag = '<link href="'.self::SHARED_CSS_HREF.'" rel="stylesheet" type="text/css"/>';
            $html = preg_replace('/<\/head>/i', $linkTag.'</head>', $html, 1) ?? ($html."\n".$linkTag);
        }

        return response($html);
    }
}

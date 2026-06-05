<?php

namespace App\Http\Controllers;

use App\Models\Webflow\BlogWebflowItem;
use App\Models\Webflow\BrandCollectionsWebflowItem;
use App\Models\Webflow\BrandsWebflowItem;
use App\Models\Webflow\WindowsWebflowItem;
use Illuminate\Support\Facades\File;

class ClassicSiteController extends Controller
{
    public function home()
    {
        $homeWindows = WindowsWebflowItem::query()
            ->where('is_archived', false)
            ->where('is_draft', false)
            ->take(6)
            ->get()
            ->map(function ($w) {
                $fd = is_array($w->field_data) ? $w->field_data : [];
                $wSlug = $fd['slug'] ?? '';
                $wName = $fd['name'] ?? '';
                $wImage = $this->extractImageUrl($fd, ['property-listing---featured-image']);
                $wSummary = $fd['property-listing---summary'] ?? '';

                return $wSlug !== ''
                    ? ['name' => $wName, 'slug' => $wSlug, 'image' => $wImage ?? '', 'summary' => $wSummary]
                    : null;
            })
            ->filter()
            ->values();

        return view('home', compact('homeWindows'));
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

        // Top window brands — referenced window-type cards (brands-types)
        $brandTypes = $window->webflowReferences('brands-types')
            ->map(function ($wt) {
                $fd = is_array($wt->field_data) ? $wt->field_data : [];
                $wtSlug = $fd['slug'] ?? '';

                $brand = $wt->webflowReference('property-listing---agent');
                $image = null;
                if ($brand) {
                    $bfd = is_array($brand->field_data) ? $brand->field_data : [];
                    $image = $this->extractImageUrl($bfd, ['brand-logo', 'logo-svg', 'agent-avatar-photo']);
                }
                if ($image === null) {
                    $image = $this->extractImageUrl($fd, [
                        'property-listing---featured-image',
                        'property-listing---thumbnail-image-v1',
                    ]);
                }

                return $wtSlug !== ''
                    ? ['name' => $fd['name'] ?? '', 'slug' => $wtSlug, 'image' => $image ?? '']
                    : null;
            })
            ->filter()
            ->values();

        $brandsTitle = $fieldData['title-for-brands'] ?? 'Top Vinyl Window Brands';

        // Learn More — referenced collections, fallback to Marvin lines on original template
        $learnMoreWindows = $this->resolveLearnMoreWindows($window);

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
            'brandTypes'       => $brandTypes,
            'brandsTitle'      => $brandsTitle,
            'learnMoreWindows' => $learnMoreWindows,
        ]);
    }

    private function resolveLearnMoreWindows(WindowsWebflowItem $window): \Illuminate\Support\Collection
    {
        $referenced = $window->webflowReferences('collections')
            ->map(function ($item) {
                $fd = is_array($item->field_data) ? $item->field_data : [];
                $itemSlug = $fd['slug'] ?? '';

                return $itemSlug !== ''
                    ? [
                        'name'  => $fd['name'] ?? '',
                        'slug'  => $itemSlug,
                        'image' => $this->extractImageUrl($fd, ['featured-image', 'property-listing---featured-image']),
                    ]
                    : null;
            })
            ->filter()
            ->values();

        if ($referenced->isNotEmpty()) {
            return $referenced;
        }

        $fallbackSlugs = ['marvin-modern', 'marvin-ultimate', 'martin-elevate'];

        return WindowsWebflowItem::query()
            ->whereIn('field_data->slug', $fallbackSlugs)
            ->get()
            ->sortBy(fn ($w) => array_search($w->field_data['slug'] ?? '', $fallbackSlugs, true))
            ->map(function ($w) {
                $fd = is_array($w->field_data) ? $w->field_data : [];

                return [
                    'name'  => $fd['name'] ?? '',
                    'slug'  => $fd['slug'] ?? '',
                    'image' => $this->extractImageUrl($fd, ['property-listing---featured-image']),
                ];
            })
            ->filter(fn ($w) => $w['slug'] !== '')
            ->values();
    }

    public function brandBySlug(string $slug)
    {
        $slug = strtolower(trim($slug));

        $brand = BrandsWebflowItem::query()
            ->where('field_data->slug', $slug)
            ->orWhere('webflow_item_id', $slug)
            ->orderByDesc('id')
            ->first();

        abort_if(! $brand, 404);

        $fieldData = is_array($brand->field_data ?? null) ? $brand->field_data : [];

        $logo        = $this->extractImageUrl($fieldData, ['brand-logo', 'logo-svg', 'agent-avatar-photo']);
        $description = $fieldData['agent-about'] ?? '';
        $name        = $fieldData['name'] ?? 'Brand';

        // Window types referenced by this brand
        $windowTypes = $brand->webflowReferences('window-types')
            ->map(function ($wt) {
                $fd    = is_array($wt->field_data) ? $wt->field_data : [];
                $wtSlug  = $fd['slug'] ?? '';
                $wtName  = $fd['name'] ?? '';
                $wtImage = $this->extractImageUrl($fd, [
                    'property-listing---thumbnail-image-v1',
                    'property-listing---featured-image',
                ]);

                return $wtSlug !== ''
                    ? ['name' => $wtName, 'slug' => $wtSlug, 'image' => $wtImage ?? '']
                    : null;
            })
            ->filter()
            ->values();

        $seoTitle       = $fieldData['seo-title']              ?? $name;
        $seoDescription = $fieldData['seo-description']        ?? '';
        $ogTitle        = $fieldData['opengraph-title']        ?? $seoTitle;
        $ogDescription  = $fieldData['opengraph-description']  ?? $seoDescription;
        $ogImage        = $fieldData['opengraph-image']        ?? $logo ?? '';
        $windowsTitle   = $fieldData['windows-titles']         ?? "Explore {$name}'s Window Types";

        return view('brands.show', [
            'brandFieldData'  => $fieldData,
            'name'            => $name,
            'slug'            => $fieldData['slug'] ?? $slug,
            'logo'            => $logo,
            'description'     => $description,
            'windowTypes'     => $windowTypes,
            'windowsTitle'    => $windowsTitle,
            'seoTitle'        => $seoTitle,
            'seoDescription'  => $seoDescription,
            'ogTitle'         => $ogTitle,
            'ogDescription'   => $ogDescription,
            'ogImage'         => $ogImage,
        ]);
    }

    public function brandCollectionBySlug(string $slug)
    {
        $slug = strtolower(trim($slug));

        $collection = BrandCollectionsWebflowItem::query()
            ->where('field_data->slug', $slug)
            ->orWhere('webflow_item_id', $slug)
            ->orderByDesc('id')
            ->first();

        abort_if(! $collection, 404);

        $fieldData = is_array($collection->field_data ?? null) ? $collection->field_data : [];

        $name          = $fieldData['name'] ?? 'Collection';
        $description   = $fieldData['description'] ?? $fieldData['long-description'] ?? '';
        $priceCategory = $fieldData['price-category'] ?? '';
        $material      = $fieldData['material'] ?? $collection->wf_material ?? '';

        // Featured image
        $featuredImage = null;
        $wfFeaturedImg = $collection->wf_featured_image;
        if (is_array($wfFeaturedImg) && isset($wfFeaturedImg['url'])) {
            $featuredImage = $wfFeaturedImg['url'];
        } else {
            $featuredImage = $this->extractImageUrl($fieldData, ['featured-image', 'property-listing---featured-image']);
        }

        // Parent brand
        $parentBrand = $collection->webflowReference('parent-brand');
        $brandName = '';
        $brandSlug = '';
        $brandLogo = null;
        if ($parentBrand) {
            $brandFd   = is_array($parentBrand->field_data) ? $parentBrand->field_data : [];
            $brandName = $brandFd['name'] ?? '';
            $brandSlug = $brandFd['slug'] ?? '';
            $brandLogo = $this->extractImageUrl($brandFd, ['brand-logo', 'logo-svg', 'agent-avatar-photo']);
        }

        // Collections tab details – window types, glass, colors, options etc.
        $tabDetails = $collection->webflowReferences('collections-tabs-details')
            ->map(function ($tab) {
                $fd = is_array($tab->field_data) ? $tab->field_data : [];
                $picture = null;
                $wfPic   = $tab->wf_picture;
                if (is_array($wfPic) && isset($wfPic['url'])) {
                    $picture = $wfPic['url'];
                } else {
                    $picture = $this->extractImageUrl($fd, ['picture', 'image']);
                }

                return [
                    'name'        => $fd['name'] ?? '',
                    'description' => $tab->wf_description ?? $fd['description'] ?? '',
                    'picture'     => $picture,
                    'category'    => strtolower($tab->wf_category ?? $fd['category'] ?? ''),
                    'subcategory' => $tab->wf_subcategory ?? $fd['subcategory'] ?? '',
                    'color'       => $tab->wf_color ?? $fd['color'] ?? '',
                ];
            })
            ->filter(fn ($t) => $t['name'] !== '')
            ->values();

        $windowTypes = $tabDetails->filter(fn ($t) => str_contains($t['category'], 'window') || str_contains($t['category'], 'type'));
        $glassItems  = $tabDetails->filter(fn ($t) => str_contains($t['category'], 'glass'));
        $colorItems  = $tabDetails->filter(fn ($t) => str_contains($t['category'], 'color'));
        $optionItems = $tabDetails->filter(fn ($t) => str_contains($t['category'], 'option') || str_contains($t['category'], 'accessor') || str_contains($t['category'], 'grid'));

        // Other collections for sidebar
        $otherCollections = $collection->webflowReferences('other-collections')
            ->map(function ($c) {
                $fd  = is_array($c->field_data) ? $c->field_data : [];
                $img = null;
                $wfImg = $c->wf_featured_image;
                if (is_array($wfImg) && isset($wfImg['url'])) {
                    $img = $wfImg['url'];
                } else {
                    $img = $this->extractImageUrl($fd, ['featured-image']);
                }

                return [
                    'name'     => $fd['name'] ?? '',
                    'slug'     => $fd['slug'] ?? '',
                    'material' => $fd['material'] ?? $c->wf_material ?? '',
                    'image'    => $img,
                ];
            })
            ->filter(fn ($c) => $c['name'] !== '' && $c['slug'] !== '')
            ->values();

        $sidebarGroups = $otherCollections->groupBy('material');

        // Advantage items
        $advantages = [];
        for ($i = 1; $i <= 4; $i++) {
            $title = $collection->{"wf_advantage_title_{$i}"} ?? $fieldData["advantage-title-{$i}"] ?? '';
            $desc  = $collection->{"wf_advantage_description_{$i}"} ?? $fieldData["advantage-description-{$i}"] ?? '';
            if ($title !== '') {
                $advantages[] = ['title' => $title, 'description' => $desc];
            }
        }

        // Inspiration photos
        $inspirationPhotos = [];
        $wfPhotos          = $collection->wf_inspiration_photos;
        if (is_array($wfPhotos)) {
            foreach ($wfPhotos as $photo) {
                if (is_array($photo) && isset($photo['url'])) {
                    $inspirationPhotos[] = $photo['url'];
                }
            }
        }
        if (empty($inspirationPhotos)) {
            $fdPhotos = $fieldData['inspiration-photos'] ?? [];
            if (is_array($fdPhotos)) {
                foreach ($fdPhotos as $photo) {
                    if (is_array($photo) && isset($photo['url'])) {
                        $inspirationPhotos[] = $photo['url'];
                    } elseif (is_string($photo) && $photo !== '') {
                        $inspirationPhotos[] = $photo;
                    }
                }
            }
        }

        $aboutDescription = $collection->wf_about_collection_description ?? $fieldData['about-collection-description'] ?? '';
        $aboutHtml        = $collection->wf_about_tab ?? '';

        $seoTitle       = $fieldData['seo-title']             ?? $name;
        $seoDescription = $fieldData['seo-description']       ?? '';
        $ogTitle        = $fieldData['opengraph-title']        ?? $seoTitle;
        $ogDescription  = $fieldData['opengraph-description'] ?? $seoDescription;
        $ogImage        = $fieldData['opengraph-image']        ?? $featuredImage ?? '';

        return view('brand-collections.show', compact(
            'fieldData', 'name', 'slug', 'description', 'priceCategory', 'material',
            'featuredImage', 'brandName', 'brandSlug', 'brandLogo',
            'windowTypes', 'glassItems', 'colorItems', 'optionItems',
            'sidebarGroups', 'advantages', 'inspirationPhotos',
            'aboutDescription', 'aboutHtml',
            'seoTitle', 'seoDescription', 'ogTitle', 'ogDescription', 'ogImage'
        ));
    }

    public function blogIndex()
    {
        $posts = BlogWebflowItem::query()
            ->where('is_archived', false)
            ->where('is_draft', false)
            ->orderByDesc('webflow_published_on')
            ->get()
            ->map(function ($post) {
                $fd = is_array($post->field_data) ? $post->field_data : [];
                $postSlug = $fd['slug'] ?? '';

                if ($postSlug === '') {
                    return null;
                }

                return [
                    'name'      => $fd['name'] ?? '',
                    'slug'      => $postSlug,
                    'image'     => $this->extractImageUrl($fd, ['main-project-image', 'client-logo']) ?? '',
                    'published' => $post->webflow_published_on?->format('M j, Y') ?? '',
                ];
            })
            ->filter()
            ->values();

        if ($posts->isEmpty()) {
            $posts = collect($this->loadBlogImportItems())
                ->map(function ($item) {
                    $fd = is_array($item['fieldData'] ?? null) ? $item['fieldData'] : [];
                    $postSlug = $fd['slug'] ?? '';

                    if ($postSlug === '') {
                        return null;
                    }

                    return [
                        'name'      => $fd['name'] ?? '',
                        'slug'      => $postSlug,
                        'image'     => $this->extractImageUrl($fd, ['main-project-image', 'client-logo']) ?? '',
                        'published' => '',
                    ];
                })
                ->filter()
                ->values();
        }

        return view('blog.index', compact('posts'));
    }

    public function blogBySlug(string $slug)
    {
        $slug = strtolower(trim($slug));
        $fieldData = $this->findBlogFieldData($slug);

        abort_if(! is_array($fieldData), 404);

        $title = $fieldData['name'] ?? 'Blog';
        $seoTitle = $fieldData['seo-title'] ?? $title;
        $seoDescription = $fieldData['seo-description'] ?? ($fieldData['project-summary'] ?? '');
        $ogTitle = $fieldData['opengraph-title'] ?? $seoTitle;
        $ogDescription = $fieldData['opengraph-description'] ?? $seoDescription;
        $heroImage = $this->extractImageUrl($fieldData, ['main-project-image', 'client-logo', 'opengraph-image']);
        $bodyHtml = $fieldData['project-details'] ?? '';

        $relatedPosts = BlogWebflowItem::query()
            ->where('is_archived', false)
            ->where('is_draft', false)
            ->orderByDesc('id')
            ->get()
            ->map(function ($post) use ($slug) {
                $fd = is_array($post->field_data) ? $post->field_data : [];
                $postSlug = $fd['slug'] ?? '';

                if ($postSlug === '' || $postSlug === $slug) {
                    return null;
                }

                return [
                    'name'  => $fd['name'] ?? '',
                    'slug'  => $postSlug,
                    'image' => $this->extractImageUrl($fd, ['main-project-image', 'client-logo']) ?? '',
                ];
            })
            ->filter()
            ->take(3)
            ->values();

        if ($relatedPosts->isEmpty()) {
            $relatedPosts = collect($this->loadBlogImportItems())
                ->map(function ($item) use ($slug) {
                    $fd = is_array($item['fieldData'] ?? null) ? $item['fieldData'] : [];
                    $postSlug = $fd['slug'] ?? '';

                    if ($postSlug === '' || $postSlug === $slug) {
                        return null;
                    }

                    return [
                        'name'  => $fd['name'] ?? '',
                        'slug'  => $postSlug,
                        'image' => $this->extractImageUrl($fd, ['main-project-image', 'client-logo']) ?? '',
                    ];
                })
                ->filter()
                ->take(3)
                ->values();
        }

        return view('blog.show', compact(
            'slug',
            'title',
            'seoTitle',
            'seoDescription',
            'ogTitle',
            'ogDescription',
            'heroImage',
            'bodyHtml',
            'relatedPosts',
        ));
    }

    private function findBlogFieldData(string $slug): ?array
    {
        $item = BlogWebflowItem::query()
            ->where('field_data->slug', $slug)
            ->orWhere('webflow_item_id', $slug)
            ->orderByDesc('id')
            ->first();

        if ($item) {
            return is_array($item->field_data) ? $item->field_data : null;
        }

        foreach ($this->loadBlogImportItems() as $importItem) {
            $fd = is_array($importItem['fieldData'] ?? null) ? $importItem['fieldData'] : [];
            if (($fd['slug'] ?? '') === $slug) {
                return $fd;
            }
        }

        return null;
    }

    private function loadBlogImportItems(): array
    {
        $path = base_path('webflow-data/current/imports/blog.json');
        if (! File::exists($path)) {
            return [];
        }

        $payload = json_decode((string) File::get($path), true);
        $items = $payload['items'] ?? [];

        return is_array($items) ? $items : [];
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

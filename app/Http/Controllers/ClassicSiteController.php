<?php

namespace App\Http\Controllers;

use App\Models\Webflow\BlogWebflowItem;
use App\Models\Webflow\BrandCollectionsWebflowItem;
use App\Models\Webflow\BrandsWebflowItem;
use App\Models\Webflow\GalleryWebflowItem;
use App\Models\Webflow\WindowsWebflowItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

    public function gallery()
    {
        $staticImages = [
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c89a4ddd537cbd43140679_IMG_8948%20copy.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c89a099acdcf47011a2c81_IMG_9092%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c899e85dd261dc57e23f89_IMG_8912%20copy.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c899b77158e4af305d0b2f_IMG_0875%20copy.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c89985de144a394c6a6916_IMG_0881%20copy.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c89963ee9a56539d6de1d5_IMG_0864%20copy.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c89943bd238c5a7b66bf4a_IMG_0867%20copy.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c8992fc7e78105e5aed93a_IMG_0227%20copy.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c8990ca76a3466d850947a_IMG_4081%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c898e282004723ddf0b3fc_IMG_0466%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c898855be0d592d5a173dd_IMG_0202%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c898137597540c9cac63f5_IMG_0195%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c897fc1177ae25483e8e48_IMG_0112%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c897d4b7a2ebaf99e490f4_IMG_0069%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c897af834d882b753a94f9_IMG_0059%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c8977c397242cfdcdaeb50_IMG_0057%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c89741737d1ddcf559e0fb_IMG_0053%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c8972e6843f4d623e8f462_IMG_0049%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c8971b3c012bcb583f91fe_IMG_0041%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c8933647d708de3996bae0_IMG_0432%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c892f96843f4d623e81a2e_IMG_0011%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c892acb4d1c57c203a9a9b_IMG_0548%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c8927034755704ad6441a7_IMG_0603%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c892543e72481766a5c610_IMG_1249%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c89209b083bfab499d45c0_IMG_1257%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c891f682004723ddef6569_IMG_1260%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c891e34c1a8f473324b118_IMG_2224%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c890599c7bd341f08e4a3e_IMG_0678%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c88f7d8fd58d9f978a8f92_IMG_1346%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c88f6d51743ff7b142dce7_IMG_1322%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c88eecc7e78105e5aca538_IMG_1496%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c88cb9172c5f771dd0b902_IMG_0619%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c88cac917e7e752c2ffb0f_IMG_3939%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c88c075e6614fb046428bc_IMG_0909%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c88bc34026fdb7f2598697_IMG_0908%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c88b898cf0554bdb23ec35_IMG_0900%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c88b62d0fc7ffcd1764a97_IMG_0896%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c88a4c7ee80cba98b9796b_IMG_2506%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c88a36a9eb08d6b08c5162_IMG_2529%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c88a22635ff53382a342f7_IMG_2533%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c889e63e44d8a594d7ee1a_IMG_0261%20copy%202.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c889a76859f9f8e2febe65_IMG_2491%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c888a737c1e1c51e1346dc_IMG_3001%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c88805a09627aa7a309b99_IMG_0862%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c887f10ee1341634cfefea_IMG_0857%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c887ac610f2fa8b924f4a5_IMG_0855%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c887905c2c53050ea7dfb5_IMG_0842%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c8877bfa1bc257940e66a1_IMG_0840%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c8832d032f5f6e5efd62be_IMG_1956%20copy1.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c88208966e1d20865bb516_824%20Huntley%20Dr%20-%20virtuallyherestudios.com-93%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c881be6a76eafd63b8b0fa_824%20Huntley%20Dr%20-%20virtuallyherestudios.com-56%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c880b868caf48c1916127f_824%20Huntley%20Dr%20-%20virtuallyherestudios.com-46%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c880613977a2ff65c0f598_824%20Huntley%20Dr%20-%20virtuallyherestudios.com-14%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c8801eb7a2ebaf99deca5c_824%20Huntley%20Dr%20-%20virtuallyherestudios.com-4%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c87f7765e49fa4f848897c_IMG_2604%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c87f1bdcf34a64421d75fe_3%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c87e439a25e87eb449765a_2%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c1e4da87641ae1b6c8388f_ML82019061_2_0.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c1e3aec656444714174803_ML82019061_6_0%20copy.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c1e3014c208aa0190f3a32_ML82019061_14_0.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c1e2c239323cddc951b404_ML82019061_5_0.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c1e26dd8be9f9d0a5a66e5_IMG_0048%20copy.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c1e18e291dd5544d66a4db_IMG_0045%20copy.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c1e11d5175ebd3c6935a09_IMG_2921%20copy1.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c1e003616a2d64c36f65fa_IMG_4488%20copy.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c1dffa190335c0d8ed49a7_IMG_2925%20copy.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/688381ac192ac8f1f8a72d92_7.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/6883819d9411c98ed1087590_1.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/6883818a51ac98fec156f4f1_3.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/688381764d4b4f0b1c7987e0_6.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/688380b679056a0be53f6d41_Frame%2026.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c883fec4b4d3727926bd2c_DSC_0300%20copy.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/6883805ca03ba6c0a0ba7581_Frame%2027.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/6883803cfdf2b295d6678634_Frame%2024.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68837d89eba765eb136ab983_Frame%2039.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68837db4472b7f5b8e77e05a_Frame%2031.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68c8850d03f555204c2ee456_IMG_3697%20copy.jpg',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68837d6bc361aa0ba1045eac_Frame%2029.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68837f8b87816ec2e8483c95_Frame%2034.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68837d5617f63550b5539b02_Frame%2017.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68837d44674b0564cb25ee4c_Frame%2016.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68837d2c60170917e2be19d0_Frame%2015.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68837d17539b7261a6d30b69_Frame%2014.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68837d02448fcace0169fb09_Frame%2012.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68837cedbacf503578cc291a_Frame%2011.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/68825b61f81ce44f12673973_Screenshot%202025-07-24%20at%2019.12.13.avif',
            'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/688236fed9cc28b78fb10d04_IMG_2963%201.avif',
        ];

        $images = $staticImages;

        try {
            $dbImages = GalleryWebflowItem::query()
                ->where('is_archived', false)
                ->where('is_draft', false)
                ->get()
                ->map(function ($item) {
                    $fd = is_array($item->field_data) ? $item->field_data : [];
                    return $this->extractImageUrl($fd, ['image', 'photo', 'picture', 'gallery-image', 'featured-image']);
                })
                ->filter()
                ->values()
                ->toArray();

            if (! empty($dbImages)) {
                $images = $dbImages;
            }
        } catch (\Throwable) {
        }

        return view('gallery', compact('images'));
    }

    public function about()
    {
        return view('about');
    }

    public function contacts()
    {
        return view('contacts');
    }

    public function specialOffers()
    {
        return view('special-offers');
    }

    public function submitContactForm(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email'     => 'required|email|max:255',
            'phone'     => 'required|string|max:50',
            'city'      => 'nullable|string|max:100',
            'message'   => 'nullable|string|max:2000',
        ]);

        session()->flash('contact_success', true);

        return redirect()->back();
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
        $posts = collect();

        try {
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
                        'published' => $this->formatBlogPublishedDate($post->webflow_published_on),
                    ];
                })
                ->filter()
                ->values();
        } catch (\Throwable) {
            $posts = collect();
        }

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
                        'published' => $this->formatBlogPublishedDate($item['lastPublished'] ?? null),
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

        $relatedPosts = collect();

        try {
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
        } catch (\Throwable) {
            $relatedPosts = collect();
        }

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
        try {
            $item = BlogWebflowItem::query()
                ->where('field_data->slug', $slug)
                ->orWhere('webflow_item_id', $slug)
                ->orderByDesc('id')
                ->first();

            if ($item) {
                return is_array($item->field_data) ? $item->field_data : null;
            }
        } catch (\Throwable) {
            // Table may be missing on staging — fall back to import JSON.
        }

        foreach ($this->loadBlogImportItems() as $importItem) {
            $fd = is_array($importItem['fieldData'] ?? null) ? $importItem['fieldData'] : [];
            if (($fd['slug'] ?? '') === $slug) {
                return $fd;
            }
        }

        return null;
    }

    private function formatBlogPublishedDate(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('M j, Y');
        }

        if (is_string($value) && $value !== '') {
            try {
                return Carbon::parse($value)->format('M j, Y');
            } catch (\Throwable) {
                return '';
            }
        }

        return '';
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

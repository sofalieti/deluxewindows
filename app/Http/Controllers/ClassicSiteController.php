<?php

namespace App\Http\Controllers;

use App\Models\Webflow\BlogWebflowItem;
use App\Models\Webflow\BrandCollectionsWebflowItem;
use App\Models\Webflow\BrandsWebflowItem;
use App\Models\Webflow\CollectionsTabsWebflowItem;
use App\Models\Webflow\CountyHubPagesWebflowItem;
use App\Models\Webflow\DoorsWebflowItem;
use App\Models\Webflow\GalleryWebflowItem;
use App\Models\Webflow\WindowReplacementWebflowItem;
use App\Models\Webflow\WindowTypeWebflowItem;
use App\Models\Webflow\WindowsWebflowItem;
use App\Services\PromotionControlService;
use App\Services\PromotionSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ClassicSiteController extends Controller
{
    /** @var list<string> */
    private const WINDOWS_INDEX_SLUG_ORDER = [
        'aluminum-clad-windows',
        'vinyl-windows',
        'wood-clad-windows',
        'fiberglass-windows',
        'wood-windows',
        'aluminum-windows',
        'steel-windows',
    ];

    public function home()
    {
        $homeWindows = WindowsWebflowItem::query()
            ->where('is_archived', false)
            ->where('is_draft', false)
            ->get()
            ->filter(function ($item) {
                $fd = is_array($item->field_data) ? $item->field_data : [];

                if (($fd['hide'] ?? false) === true) {
                    return false;
                }

                if (($fd['parent-collection'] ?? '') !== 'Windows') {
                    return false;
                }

                return ($fd['slug'] ?? '') !== '';
            })
            ->sortBy(function ($item) {
                $slug = (string) data_get($item->field_data, 'slug', '');
                $pos = array_search($slug, self::WINDOWS_INDEX_SLUG_ORDER, true);

                return $pos === false ? 999 : $pos;
            })
            ->values()
            ->take(4)
            ->map(function ($w) {
                $fd = is_array($w->field_data) ? $w->field_data : [];
                $wSlug = $fd['slug'] ?? '';
                $wName = $fd['name'] ?? '';
                $wImage = $this->extractImageUrl($fd, ['property-listing---featured-image']);
                $wSummary = $fd['property-listing---excerpt'] ?? '';

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

        $heroImage = $window->customHeroImageUrl()
            ?? $this->extractImageUrl($fieldData, [
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
        $controls       = app(PromotionControlService::class);
        $windowPricing  = $controls->windowTypePricing((string) ($fieldData['slug'] ?? $slug));
        $discountHtml   = $windowPricing
            ? $controls->priceHtml($windowPricing['base'], $windowPricing['final'], 'per window installed')
            : $this->legacyDiscountToPromoHtml((string) ($fieldData['discounttext'] ?? ''), 'per window installed');

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
            'discountHtml'     => $discountHtml,
            'warrantyHtml'     => $fieldData['warrantytext'] ?? '',
            'heroImage'        => $heroImage ?? '',
            'galleryImages'    => $galleryImages,
            'brandTypes'       => $brandTypes,
            'brandsTitle'      => $brandsTitle,
            'learnMoreWindows' => $learnMoreWindows,
        ]);
    }

    public function doorBySlug(string $slug)
    {
        $slug = strtolower(trim($slug));

        $door = DoorsWebflowItem::query()
            ->where('field_data->slug', $slug)
            ->orWhere('webflow_item_id', $slug)
            ->orderByDesc('id')
            ->first();

        abort_if(! $door, 404);

        $fieldData = is_array($door->field_data ?? null) ? $door->field_data : [];

        $heroImage = $this->extractImageUrl($fieldData, [
            'blog-post---featured-image',
        ]);

        $mainImage = $this->extractImageUrl($fieldData, [
            'blog-post---thumbnail-image-v1',
        ]);

        $galleryImages = collect(data_get($fieldData, 'gallery', []))
            ->map(fn ($img) => is_array($img) ? ($img['url'] ?? null) : (is_string($img) ? $img : null))
            ->filter()
            ->values();

        $doorBrands = $door->webflowReferences('doors-brands')
            ->map(function ($brand) {
                $fd = is_array($brand->field_data) ? $brand->field_data : [];
                $brandSlug = $fd['slug'] ?? '';
                $image = $this->extractImageUrl($fd, ['brand-logo', 'logo-svg', 'agent---avatar-photo', 'agent-avatar-photo']);

                return $brandSlug !== ''
                    ? ['name' => $fd['name'] ?? '', 'slug' => $brandSlug, 'image' => $image ?? '']
                    : null;
            })
            ->filter()
            ->values();

        $brandsTitle = $fieldData['brands-title'] ?? 'Top Door Brands';
        $learnMoreDoors = $this->resolveLearnMoreDoors($door);

        $seoTitle       = $fieldData['seo-title'] ?? ($fieldData['name'] ?? 'Doors');
        $seoDescription = $fieldData['seo-description'] ?? ($fieldData['description'] ?? '');
        $ogTitle        = $fieldData['opengraph-title'] ?? $seoTitle;
        $ogDescription  = $fieldData['opengraph-description'] ?? $seoDescription;
        $ogImage        = $fieldData['opengraph-image']
            ?? $this->extractImageUrl($fieldData, ['blog-post---thumbnail-image-v3', 'blog-post---featured-image'])
            ?? $mainImage
            ?? '';

        return view('doors.show', [
            'doorFieldData'  => $fieldData,
            'seoTitle'       => $seoTitle,
            'seoDescription' => $seoDescription,
            'ogTitle'        => $ogTitle,
            'ogDescription'  => $ogDescription,
            'ogImage'        => $ogImage,
            'title'          => $fieldData['name'] ?? 'Door',
            'slug'           => $fieldData['slug'] ?? $slug,
            'summary'        => $fieldData['description'] ?? '',
            'aboutHtml'      => $fieldData['blog-post---rich-text'] ?? $door->wf_blog_post_rich_text ?? '',
            'discountHtml'   => $fieldData['door-discount'] ?? $door->wf_door_discount ?? '',
            'heroImage'      => $heroImage ?? '',
            'mainImage'      => $mainImage ?? '',
            'galleryImages'  => $galleryImages,
            'doorBrands'     => $doorBrands,
            'brandsTitle'    => $brandsTitle,
            'learnMoreDoors' => $learnMoreDoors,
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

    public function glossary()
    {
        $seoTitle       = 'Window & Door Glossary | Deluxe Windows – Bay Area';
        $seoDescription = 'Not sure what low-E glass or casement means? Explore our window and door glossary to understand key terms and make informed decisions for your Bay Area home.';
        $ogImage        = 'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd/684da952cef202b8dda5788c_Meta%20cover-2.jpg';

        $navItems = [
            ['id' => 'styling', 'label' => 'Window Replacement'],
            ['id' => 'editing-pages', 'label' => 'Energy Efficient windows'],
            ['id' => 'useful-notes', 'label' => 'Energy Star'],
        ];

        $sections = [
            [
                'id'    => 'styling',
                'title' => 'Windows Replacement',
                'blocks' => [
                    ['tag' => 'p', 'class' => 'paragraph-35', 'html' => 'The thought of replacing all or most of your old windows with new ones can be daunting and understandably so. Replacing windows throughout your house could be expensive, not to mention inconvenient but don&#x27;t get too discouraged right away. With proper planning to ensure you install the right windows, ones that meet your needs, vision, and budget, this could be one of the best and most financially wise decisions of your life.'],
                    ['tag' => 'h4', 'class' => 'mg-bottom-extra-small', 'html' => 'Understand why window replacement is necessary.'],
                    ['tag' => 'p', 'class' => 'paragraph-36', 'html' => 'There are many reasons that people choose to replace old windows; Appearance, deterioration, maintenance and energy efficiency are just a few. Once you decide to purchase new windows for your home and the reason you are replacing them, you automatically narrow down your search, making it easier to reach a decision. Some materials may be better for energy efficiency, others because they fit in with the aesthetic of your home.'],
                    ['tag' => 'h4', 'class' => 'mg-bottom-extra-small', 'html' => 'Set a budget.'],
                    ['tag' => 'p', 'class' => 'paragraph-38', 'html' => 'Deciding on the number of windows you need to replace and setting a budget that works for you should be your next step. Windows are generally priced very differently; hence, the number of windows you will be replacing will help you determine the most economical style of window.'],
                    ['tag' => 'h4', 'class' => 'mg-bottom-extra-small', 'html' => 'Choose your style.'],
                    ['tag' => 'p', 'class' => 'paragraph-39', 'html' => 'The best way to start is to think about the rooms in which the windows will be placed. Do they face the front of the house or are they hidden at the back? It&#x27;s more than acceptable to use several styles throughout your home, keep in mind, however, that some windows need a little more maintenance than others. For example wood windows require a little more up-keep than <a href="/windows/vinyl-windows">vinyl</a>, which are virtually maintenance free. However the aesthetic benefits of wood usually far out way the maintenance they need over the years, as always personal preference is key.'],
                    ['tag' => 'h4', 'class' => 'mg-bottom-extra-small', 'html' => 'How can I get more information?'],
                    ['tag' => 'p', 'class' => 'paragraph-40', 'html' => 'When you request a <a href="/contacts">free quote</a> you will have a chance to learn more about your options and speak with a licensed home improvement professional. We&#x27;ll make sure that you have the information you need to make a well thought out decision that&#x27;s right for you.<br/>'],
                ],
            ],
            [
                'id'    => 'editing-pages',
                'title' => 'Energy Efficient Windows',
                'blocks' => [
                    ['tag' => 'h4', 'class' => 'mg-bottom-extra-small', 'html' => 'What are Green or Energy Efficient windows?'],
                    ['tag' => 'p', 'class' => 'mg-bottom-default', 'html' => 'Green or energy efficient, windows are windows that transmit the least amount of heat and reduce your home energy output. They also protect your home temperature from being affected by the external weather.<br/><br/>- Most of the heat loss in the home is caused by the windows, because of the type of glass and/or type of frame. This is because older windows, typically aluminum windows, are conductors meaning they transmit heat and cold.<br/>- Installing green windows into your home will not only maintain the desired temperature of your home but in the long run will save you money on heating and cooling bills. Windows that are considered &quot;green&quot; insulate your home and decrease heat transfer making your home more energy efficient.'],
                    ['tag' => 'h4', 'class' => 'mg-bottom-extra-small', 'html' => 'What determines energy efficiency of a window?'],
                    ['tag' => 'p', 'class' => 'mg-bottom-default', 'html' => 'The type of glass used for a window, determines its efficiency rate how much and what kind of light will be let in. <br/>- The newest technology for glass allows for a large reduction in heat loss. With a particular type of glass called &quot;Low-E,&quot; or low emissivity, energy efficiency becomes possible.<br/>- Low-E glass allows the visible light to enter the home, but blocks the bad light that damages skin and cause colors to fade. For the highest efficiency, the space between the panes is filled with argon gas to further insulate.<br/>- Warm edge spacers in between the panes also reduce heat transfer as well as insulate the edges creating a highly efficient &quot;green&quot; window.<br/>- For even greater efficiency, the frames of energy efficient windows are made with materials that also help reduce heat transfer while increasing insulation, like vinyl or fiberglass.<br/><br/>The circle graph above shows that one-quarter of your carbon footprint consists of home energy use, but this can vary depending on the kinds of energy sources available to power your home. ENERGY STAR calculates carbon savings for ENERGY STAR windows, doors, and skylights based on the mix of fuels in a region and the estimated energy use for a typical home.'],
                ],
            ],
            [
                'id'    => 'useful-notes',
                'title' => 'Energy Star',
                'blocks' => [
                    ['tag' => 'p', 'class' => 'paragraph-41', 'html' => 'ENERGY STAR is the trusted, government-backed symbol for energy efficiency helping us all save money and protect the environment through energy-efficient products and practices.<br/>‍<br/>The ENERGY STAR label was established to:Reduce greenhouse gas emissions and other pollutants caused by the inefficient use of energy; andMake it easy for consumers to identify and purchase energy-efficient products that offer savings on energy bills without sacrificing performance, features, and comfort.'],
                    ['tag' => 'h4', 'class' => 'mg-bottom-extra-small', 'html' => 'How Does EPA Choose which Products Earn the Label?'],
                    ['tag' => 'p', 'class' => 'mg-bottom-default', 'html' => 'Products can earn the ENERGY STAR label by meeting the energy efficiency requirements set forth in ENERGY STAR product specifications. EPA establishes these specifications based on the following set of key guiding principles:<br/>- Product categories must contribute significant energy savings nationwide.<br/>- Qualified products must deliver the features and performance demanded by consumers, in addition to increased energy efficiency.<br/>- If the qualified product costs more than a conventional, less-efficient counterpart, purchasers will recover their investment in increased energy efficiency through utility bill savings, within a reasonable period of time.<br/>- Energy efficiency can be achieved through broadly available, non-proprietary technologies offered by more than one manufacturer.<br/>- Product energy consumption and performance can be measured and verified with testing.<br/>- Labeling would effectively differentiate products and be visible for purchasers.<br/><br/>ENERGY STAR is the trusted, government-backed symbol for energy efficiency helping us all save money and protect the environment through energy-efficient products and practices.<br/><br/>The ENERGY STAR label was established to:<br/>- Reduce greenhouse gas emissions and other pollutants caused by the inefficient use of energy; <br/>- Make it easy for consumers to identify and purchase energy-efficient products that offer savings on energy bills without sacrificing performance, features, and comfort.'],
                    ['tag' => 'h4', 'class' => 'mg-bottom-extra-small', 'html' => 'How Does EPA Choose which Products Earn the Label?'],
                    ['tag' => 'p', 'class' => 'paragraph-42', 'html' => 'Products can earn the ENERGY STAR label by meeting the energy efficiency requirements set forth in ENERGY STAR product specifications. EPA establishes these specifications based on the following set of key guiding principles:<br/>- Product categories must contribute significant energy savings nationwide.<br/>- Qualified products must deliver the features and performance demanded by consumers, in addition to increased energy efficiency.<br/>- If the qualified product costs more than a conventional, less-efficient counterpart, purchasers will recover their investment in increased energy efficiency through utility bill savings, within a reasonable period of time.<br/>- Energy efficiency can be achieved through broadly available, non-proprietary technologies offered by more than one manufacturer.<br/>- Product energy consumption and performance can be measured and verified with testing.Labeling would effectively differentiate products and be visible for purchasers.'],
                ],
            ],
        ];

        return view('glossary', compact('seoTitle', 'seoDescription', 'ogImage', 'navItems', 'sections'));
    }

    public function faq()
    {
        $seoTitle       = 'Window & Door FAQs | Deluxe Windows – Bay Area';
        $seoDescription = 'Have questions about window or door replacement in San Francisco? Get expert answers from Deluxe Windows on installation timelines, permits, energy savings, and costs.';
        $ogImage        = 'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd/684da952cef202b8dda5788c_Meta%20cover-2.jpg';

        $navItems = [
            ['id' => 'materials', 'label' => 'Which material is best for your windows?'],
            ['id' => 'obtain', 'label' => 'Will I need to obtain a permit?'],
            ['id' => 'new', 'label' => 'When do I need new windows?'],
            ['id' => 'causes', 'label' => 'What causes condensation?'],
            ['id' => 'retrofit', 'label' => 'Will I need retrofit or new construction installation?'],
            ['id' => 'brands', 'label' => 'How to choose windows brands and styles?'],
        ];

        $sections = [
            [
                'id'    => 'materials',
                'title' => 'Which material is best for your windows?',
                'blocks' => [
                    ['tag' => 'h4', 'class' => 'mg-bottom-extra-small', 'html' => 'Vinyl windows'],
                    ['tag' => 'p', 'class' => 'mg-bottom-default', 'html' => 'Made from a plastic material, energy efficient and low maintenance. These windows work best in a stable climate without extreme weather conditions. Vinyl windows last a long time due to the durable and sturdy material from which they&#x27;re made.'],
                    ['tag' => 'h4', 'class' => 'mg-bottom-extra-small', 'html' => 'Wood windows '],
                    ['tag' => 'p', 'class' => 'mg-bottom-default', 'html' => 'Typically used to preserve the existing look of a home. More costly than other types of windows they require some maintenance and upkeep. These windows are beautiful hand-crafted pieces of furniture adding to the overall aesthetic nature of your home. Wood windows are a unique and elegant addition to your home that no other window can match.'],
                    ['tag' => 'h4', 'class' => 'mg-bottom-extra-small', 'html' => 'Wood-Cladwindows'],
                    ['tag' => 'p', 'class' => 'mg-bottom-default', 'html' => 'A combination material with a wood interior and either vinyl/aluminum/fiberglass exterior, provide the same look as fully wood windows without the required maintenance over time.'],
                    ['tag' => 'h4', 'class' => 'mg-bottom-extra-small', 'html' => 'Aluminum windows'],
                    ['tag' => 'p', 'class' => 'mg-bottom-default', 'html' => 'Strong, light, and require minimal maintenance. Aluminum is sensitive to temperature changes resulting in lower insulation levels and may be subject to condensation. Though these characteristics cause aluminum to be less energy efficient, it offers the thinnest frame, allowing for maximum daylight exposure throughout the day. To increase energy efficiency there are thermal broken aluminum windows. These windows have thermal breakers, also known as spacers, which are inserted in-between the aluminum frame, in order to reduce conductivity. This makes the window less conducive and more energy efficient.<br/>'],
                    ['tag' => 'h4', 'class' => 'mg-bottom-extra-small', 'html' => 'Fiberglass windows'],
                    ['tag' => 'p', 'class' => 'mg-bottom-default', 'html' => 'Have become a top of the line product because of the durable and long lasting window framing material. Fiberglass is about 3 times stronger than aluminum and 9 times stronger than vinyl, so these windows should last the longest. Fiberglass windows are durable and are practically maintenance free. Since full fiberglass windows are quite costly, there are fiberglass composite windows, which are not full fiberglass windows. Fibrex windows, a type of composite window, are made from few durable materials combined. The composite contains wood particles as well as fiberglass and therefore are considered very durable. These windows are supposed to last longer than vinyl windows with minimal maintenance.<br/>'],
                ],
            ],
            [
                'id'    => 'obtain',
                'title' => 'Will I need to obtain a permit?',
                'blocks' => [
                    ['tag' => 'p', 'class' => 'mg-bottom-default', 'html' => 'Many cities, such as San Francisco, Oakland, Berkeley and many others, have area specific requirements to meet historic preservation and other city codes. It is best to contact your city Planning/Building department to help you determine whether a permit is required for your home.<br/><br/>If you reside in a home that is part of a Homeowner&#x27;s Association (H.O.A.), it is best to check their requirements first. The H.O.A. will typically have a certain style or brand that they require and will have to approve your project before a permit can be obtained.<br/><br/>If a permit is required, Deluxe Windows can walk you through the process, or take care of it for you. The permit process is typically long and tedious including forms, floor plan drawings, pictures and other items may be required by the city. That&#x27;s why choosing BAWP Inc. brings you extra benefits. After years of dealing with building departments our expertise on what is required expedites the process, getting your project approved and on the way faster and and more efficiently.'],
                ],
            ],
            [
                'id'    => 'new',
                'title' => 'When do I need new windows?',
                'blocks' => [
                    ['tag' => 'p', 'class' => 'paragraph-45', 'html' => 'If you aren&#x27;t sure whether your windows need replacing, Deluxe Windos, Inc. can come to your home for a free consultation.<br/>‍<br/>Signs that it may be time to change your windows are:<br/>- High electric billsYour home is way too hot in summer, and way too cold in winter;<br/>- Consistent condensation on the panes of your windows; <br/>- Drafty or leaking windows;<br/>- Difficult to open or close.'],
                ],
            ],
            [
                'id'    => 'causes',
                'title' => 'What causes condensation?',
                'blocks' => [
                    ['tag' => 'p', 'class' => 'paragraph-46', 'html' => 'Condensation occurs because of humidity that is naturally present in the air. When water vapor comes into contact with surfaces that are cooler than it is, the vapor forms into visible moisture, which gets trapped in between the glass. Old windows have a tendency to have this problem because insulation technology was not as advanced as it is today.'],
                ],
            ],
            [
                'id'    => 'retrofit',
                'title' => 'Will I need retrofit or new construction installation?',
                'blocks' => [
                    ['tag' => 'p', 'class' => 'paragraph-47', 'html' => 'Most people will choose to get retrofit installation because it is an easier, shorter, and a cheaper process than new construction installation. A retrofit window is custom fit to the existing opening, and is installed without disturbing the exterior area around the window. Retrofit windows slightly decrease the existing daylight opening in order for the window to fit inside the existing opening. A new construction installation removes the entire window including the surrounding frame. The new window is installed in multiple steps, like waterproofing and flashing, before the new trim and caulking takes place. '],
                ],
            ],
            [
                'id'    => 'brands',
                'title' => 'How to choose windows brands and styles?',
                'blocks' => [
                    ['tag' => 'p', 'class' => 'paragraph-48', 'html' => 'The answer to this question can only be answered once we come to your home for a free consultation. Every home is different, and when our professional window replacement specialist comes out to assess your house, we can factor in all the different aspects to suggest which product, style and price range will work best for you. Since Deluxe Windows, Inc. carries over 20 different brands we can offer you a wide range of choices to specifically fit your needs.<br/>'],
                ],
            ],
        ];

        return view('faq', compact('seoTitle', 'seoDescription', 'ogImage', 'navItems', 'sections'));
    }

    public function testimonials()
    {
        $seoTitle       = 'Customer Reviews | Deluxe Windows – Bay Area';
        $seoDescription = 'See why Bay Area homeowners trust Deluxe Windows. Read verified customer reviews on window and door installations, service quality, and overall experience.';
        $ogImage        = 'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd/684da952cef202b8dda5788c_Meta%20cover-2.jpg';

        return view('testimonials', compact('seoTitle', 'seoDescription', 'ogImage'));
    }

    public function financing()
    {
        $seoTitle       = 'Window & Door Financing | Deluxe Windows – Bay Area';
        $seoDescription = 'Make your window and door replacement affordable. Deluxe Windows offers flexible financing options in San Francisco so Bay Area homeowners can upgrade now and pay over time.';
        $ogImage        = 'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd/684da952cef202b8dda5788c_Meta%20cover-2.jpg';

        return view('financing', compact('seoTitle', 'seoDescription', 'ogImage'));
    }

    public function about()
    {
        return view('about');
    }

    public function contacts()
    {
        return view('contacts');
    }

    public function specialOffers(PromotionSettingsService $promotions)
    {
        $coupons = $promotions->publishedCoupons()->map(function ($coupon) use ($promotions) {
            $fieldData = is_array($coupon->field_data) ? $coupon->field_data : [];
            $featured = $fieldData['featured-image'] ?? $coupon->wf_featured_image;
            $imageUrl = is_array($featured) ? ($featured['url'] ?? '') : '';

            return [
                'name' => (string) ($fieldData['name'] ?? ''),
                'description' => (string) ($fieldData['blog-post-category---description'] ?? ''),
                'image' => $imageUrl,
                'expires_label' => $promotions->couponExpiresLabel($coupon, 'long'),
            ];
        });

        return view('special-offers', compact('coupons'));
    }

    public function submitContactForm(Request $request)
    {
        $payload = [
            'full_name' => trim((string) ($request->input('full_name') ?: $request->input('Name'))),
            'email' => trim((string) ($request->input('email') ?: $request->input('Email'))),
            'phone' => trim((string) ($request->input('phone') ?: $request->input('Phone'))),
            'city' => trim((string) ($request->input('city') ?: $request->input('Subject'))),
            'message' => trim((string) ($request->input('message') ?: $request->input('Message'))),
            'page_url' => trim((string) ($request->input('page_url') ?: $request->headers->get('referer', ''))),
            'utm_source' => trim((string) $request->input('utm_source')),
            'utm_medium' => trim((string) $request->input('utm_medium')),
            'utm_campaign' => trim((string) $request->input('utm_campaign')),
        ];

        $validated = validator($payload, [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'city' => 'nullable|string|max:100',
            'message' => 'nullable|string|max:3000',
            'page_url' => 'nullable|string|max:1000',
            'utm_source' => 'nullable|string|max:255',
            'utm_medium' => 'nullable|string|max:255',
            'utm_campaign' => 'nullable|string|max:255',
        ])->validate();

        $subject = 'New Deluxe Windows lead: '.$validated['full_name'];
        $bodyLines = [
            'Name: '.$validated['full_name'],
            'Email: '.$validated['email'],
            'Phone: '.$validated['phone'],
            'City: '.($validated['city'] !== '' ? $validated['city'] : '-'),
            'Message: '.($validated['message'] !== '' ? $validated['message'] : '-'),
            'Page: '.($validated['page_url'] !== '' ? $validated['page_url'] : '-'),
            'UTM Source: '.($validated['utm_source'] !== '' ? $validated['utm_source'] : '-'),
            'UTM Medium: '.($validated['utm_medium'] !== '' ? $validated['utm_medium'] : '-'),
            'UTM Campaign: '.($validated['utm_campaign'] !== '' ? $validated['utm_campaign'] : '-'),
        ];

        Mail::raw(implode("\n", $bodyLines), function ($message) use ($subject, $validated): void {
            $message->to('sofalieti@gmail.com')
                ->replyTo($validated['email'], $validated['full_name'])
                ->subject($subject);
        });

        $bridgeUrls = (array) config('services.lead_bridge.urls', []);
        foreach ($bridgeUrls as $bridgeUrl) {
            if (! is_string($bridgeUrl) || trim($bridgeUrl) === '') {
                continue;
            }
            try {
                Http::asForm()
                    ->timeout(8)
                    ->post($bridgeUrl, [
                        'Name' => $validated['full_name'],
                        'Email' => $validated['email'],
                        'Phone' => $validated['phone'],
                        'Subject' => $validated['city'],
                        'Message' => $validated['message'],
                        'URL' => $validated['page_url'],
                        'utm_source' => $validated['utm_source'],
                        'utm_medium' => $validated['utm_medium'],
                        'utm_campaign' => $validated['utm_campaign'],
                    ]);
            } catch (\Throwable $e) {
                Log::warning('Lead bridge request failed', [
                    'url' => $bridgeUrl,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        session()->flash('contact_success', true);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->back()->with('contact_success', true);
    }

    private function resolveLearnMoreWindows(WindowsWebflowItem $window): \Illuminate\Support\Collection
    {
        $currentSlug = strtolower(trim((string) data_get($window->field_data, 'slug', '')));

        $referenced = $window->webflowReferences('collections')
            ->map(function ($item) use ($currentSlug) {
                $fd = is_array($item->field_data) ? $item->field_data : [];
                $itemSlug = strtolower(trim((string) ($fd['slug'] ?? '')));

                if ($itemSlug === '' || $itemSlug === $currentSlug) {
                    return null;
                }

                $linkedWindow = WindowsWebflowItem::query()
                    ->where('is_archived', false)
                    ->where('is_draft', false)
                    ->where('field_data->slug', $itemSlug)
                    ->first();

                if ($linkedWindow !== null) {
                    $winFd = is_array($linkedWindow->field_data) ? $linkedWindow->field_data : [];
                    if (! $this->isVisibleWindowsMaterialPage($winFd)) {
                        return null;
                    }

                    return $this->mapLearnMoreWindowCard($linkedWindow);
                }

                return [
                    'name'  => $fd['name'] ?? '',
                    'slug'  => $itemSlug,
                    'image' => $this->extractImageUrl($fd, ['featured-image', 'property-listing---featured-image']) ?? '',
                ];
            })
            ->filter()
            ->values();

        if ($referenced->isNotEmpty()) {
            return $referenced;
        }

        return $this->learnMoreMaterialWindows($currentSlug);
    }

    private function learnMoreMaterialWindows(string $excludeSlug): \Illuminate\Support\Collection
    {
        return WindowsWebflowItem::query()
            ->where('is_archived', false)
            ->where('is_draft', false)
            ->get()
            ->filter(function ($item) use ($excludeSlug) {
                $fd = is_array($item->field_data) ? $item->field_data : [];

                return $this->isVisibleWindowsMaterialPage($fd, $excludeSlug);
            })
            ->sortBy(function ($item) {
                $slug = (string) data_get($item->field_data, 'slug', '');
                $pos = array_search($slug, self::WINDOWS_INDEX_SLUG_ORDER, true);

                return $pos === false ? 999 : $pos;
            })
            ->map(fn ($item) => $this->mapLearnMoreWindowCard($item))
            ->values();
    }

    private function isVisibleWindowsMaterialPage(array $fieldData, string $excludeSlug = ''): bool
    {
        if (($fieldData['hide'] ?? false) === true) {
            return false;
        }

        if (($fieldData['parent-collection'] ?? '') !== 'Windows') {
            return false;
        }

        $slug = strtolower(trim((string) ($fieldData['slug'] ?? '')));

        return $slug !== '' && $slug !== strtolower($excludeSlug);
    }

    private function mapLearnMoreWindowCard(WindowsWebflowItem $window): array
    {
        $fd = is_array($window->field_data) ? $window->field_data : [];

        return [
            'name'  => $fd['name'] ?? '',
            'slug'  => $fd['slug'] ?? '',
            'image' => $this->extractImageUrl($fd, [
                'property-listing---featured-image',
                'property-listing---thumbnail-image-v1',
            ]) ?? '',
        ];
    }

    private function resolveLearnMoreDoors(DoorsWebflowItem $door): \Illuminate\Support\Collection
    {
        return DoorsWebflowItem::query()
            ->where('is_archived', false)
            ->where('is_draft', false)
            ->orderBy('id')
            ->get()
            ->map(function ($item) {
                $fd = is_array($item->field_data) ? $item->field_data : [];
                $itemSlug = $fd['slug'] ?? '';

                return $itemSlug !== ''
                    ? [
                        'name'  => $fd['name'] ?? '',
                        'slug'  => $itemSlug,
                        'image' => $this->extractImageUrl($fd, ['blog-post---thumbnail-image-v1', 'blog-post---featured-image']),
                    ]
                    : null;
            })
            ->filter()
            ->values();
    }

    public function windowsIndex()
    {
        $seoTitle       = 'Windows for Bay Area Homes | Deluxe Windows California';
        $seoDescription = 'Discover high-performance windows for San Francisco homes. Deluxe Windows installs vinyl, wood, aluminum, and fiberglass windows with expert craftsmanship. Get a free quote.';
        $ogImage        = 'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd/684da9f6a8d9aab7e88572b2_Meta%20cover-windows.jpg';

        $windows = WindowsWebflowItem::query()
            ->where('is_archived', false)
            ->where('is_draft', false)
            ->get()
            ->filter(function ($item) {
                $fd = is_array($item->field_data) ? $item->field_data : [];

                if (($fd['hide'] ?? false) === true) {
                    return false;
                }

                if (($fd['parent-collection'] ?? '') !== 'Windows') {
                    return false;
                }

                return ($fd['slug'] ?? '') !== '';
            })
            ->sortBy(function ($item) {
                $slug = (string) data_get($item->field_data, 'slug', '');
                $pos = array_search($slug, self::WINDOWS_INDEX_SLUG_ORDER, true);

                return $pos === false ? 999 : $pos;
            })
            ->values()
            ->take(6)
            ->map(fn ($item) => $this->mapWindowsIndexCard($item))
            ->filter(fn ($card) => ($card['slug'] ?? '') !== '')
            ->values();

        return view('windows.index', compact('seoTitle', 'seoDescription', 'ogImage', 'windows'));
    }

    public function brandIndex()
    {
        $seoTitle       = 'Top Window & Door Brands | Deluxe Windows – Bay Area';
        $seoDescription = 'Deluxe Windows partners with Andersen, Marvin, Milgard, Simonton & more. Explore premium window and door brands trusted by Bay Area homeowners. Request a free estimate.';
        $ogImage        = 'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd/684da9f6a8d9aab7e88572b2_Meta%20cover-windows.jpg';

        $brands = BrandsWebflowItem::query()
            ->where('is_archived', false)
            ->where('is_draft', false)
            ->get()
            ->sortBy(fn ($brand) => (int) data_get($brand->field_data, 'order', 999))
            ->values()
            ->map(fn ($brand) => $this->mapBrandIndexCard($brand))
            ->filter(fn ($card) => ($card['slug'] ?? '') !== '')
            ->values();

        return view('brand.index', compact('seoTitle', 'seoDescription', 'ogImage', 'brands'));
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

        $logoSvg     = $this->extractImageUrl($fieldData, ['logo-svg']);
        $logo        = $logoSvg ?? $this->extractImageUrl($fieldData, ['brand-logo', 'agent---avatar-photo', 'agent-avatar-photo']);
        $description = $fieldData['agent---about'] ?? $brand->wf_agent_about ?? '';
        $featuredImage = $this->extractImageUrl($fieldData, ['featured-image']);
        if ($featuredImage === null && is_array($brand->wf_featured_image ?? null)) {
            $featuredImage = $brand->wf_featured_image['url'] ?? null;
        }
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

        $doorTypes = $brand->webflowReferences('doors-type-marvin')
            ->map(function ($dt) {
                $fd = is_array($dt->field_data) ? $dt->field_data : [];
                $dtSlug = $fd['slug'] ?? '';
                $dtName = $fd['name'] ?? '';
                $dtImage = $this->extractImageUrl($fd, [
                    'property-listing---thumbnail-image-v1',
                    'property-listing---featured-image',
                ]);

                return $dtSlug !== ''
                    ? ['name' => $dtName, 'slug' => $dtSlug, 'image' => $dtImage ?? '']
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
        $doorsTitle     = $fieldData['doors-title']            ?? "Explore {$name}'s Door Types";
        $sidebarMaterialGroups = $this->buildBrandSidebarMaterialGroups($brand, $fieldData);
        $controls = app(PromotionControlService::class);
        $brandPricing = $this->resolveBrandPromotionPricing($brand, (string) ($fieldData['slug'] ?? $slug), $controls);
        $brandHeroFormHtml = $brandPricing
            ? $controls->priceHtml($brandPricing['base'], $brandPricing['final'])
            : null;

        return view('brands.show', [
            'brandFieldData'  => $fieldData,
            'name'            => $name,
            'slug'            => $fieldData['slug'] ?? $slug,
            'logo'            => $logo,
            'featuredImage'   => $featuredImage,
            'description'     => $description,
            'windowTypes'     => $windowTypes,
            'doorTypes'       => $doorTypes,
            'sidebarMaterialGroups' => $sidebarMaterialGroups,
            'windowsTitle'    => $windowsTitle,
            'doorsTitle'      => $doorsTitle,
            'seoTitle'        => $seoTitle,
            'seoDescription'  => $seoDescription,
            'ogTitle'         => $ogTitle,
            'ogDescription'   => $ogDescription,
            'ogImage'         => $ogImage,
            'brandHeroFormHtml' => $brandHeroFormHtml,
        ]);
    }

    public function windowTypeBySlug(string $slug)
    {
        $slug = strtolower(trim($slug));

        $windowType = WindowTypeWebflowItem::query()
            ->where('field_data->slug', $slug)
            ->orWhere('webflow_item_id', $slug)
            ->orderByDesc('id')
            ->first();

        abort_if(! $windowType, 404);

        $fieldData = is_array($windowType->field_data ?? null) ? $windowType->field_data : [];

        $name  = $fieldData['name'] ?? 'Window Type';
        $about = $fieldData['property-listing---about'] ?? '';
        $featuredImage = $this->extractImageUrl($fieldData, [
            'property-listing---featured-image',
            'property-listing---thumbnail-image-v1',
        ]);

        $parentBrand = $windowType->webflowReference('property-listing---agent');
        abort_if(! $parentBrand, 404);

        $brandFd = is_array($parentBrand->field_data) ? $parentBrand->field_data : [];
        $brandName = $brandFd['name'] ?? '';
        $brandSlug = $brandFd['slug'] ?? '';
        $brandLogo = $this->extractImageUrl($brandFd, ['logo-svg', 'brand-logo', 'agent---avatar-photo', 'agent-avatar-photo']);

        $windowTypes = $parentBrand->webflowReferences('window-types')
            ->map(function ($wt) {
                $fd = is_array($wt->field_data) ? $wt->field_data : [];
                $wtSlug = $fd['slug'] ?? '';
                $wtName = $fd['name'] ?? '';
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

        $sidebarMaterialGroups = $this->buildBrandSidebarMaterialGroups($parentBrand, $brandFd);

        $collectionsField = ($fieldData['new-template'] ?? false)
            ? 'collections-new-template'
            : 'window-type-collection';

        $collections = $windowType->webflowReferences($collectionsField)
            ->map(function ($collection) {
                $fd = is_array($collection->field_data) ? $collection->field_data : [];
                $image = $this->extractImageUrl($fd, ['featured-image', 'property-type---icon']);
                if ($image === null && is_array($collection->wf_featured_image ?? null)) {
                    $image = $collection->wf_featured_image['url'] ?? null;
                }

                return [
                    'name'  => $fd['name'] ?? '',
                    'slug'  => $fd['slug'] ?? '',
                    'image' => $image ?? '',
                ];
            })
            ->filter(fn ($c) => $c['name'] !== '' && $c['slug'] !== '')
            ->values();

        $collectionsTitle = $fieldData['title'] ?? "Explore {$brandName} Collections";
        $heroFormHtml     = $this->resolveWindowTypeHeroPricing($windowType, $fieldData);

        $seoTitle       = $fieldData['seo-title'] ?? $name;
        $seoDescription = $fieldData['seo-description'] ?? '';
        $ogTitle        = $fieldData['opengraph-title'] ?? $seoTitle;
        $ogDescription  = $fieldData['opengraph-description'] ?? $seoDescription;
        $ogImage        = $fieldData['opengraph-image'] ?? $featuredImage ?? $brandLogo ?? '';

        return view('window-types.show', [
            'name'                  => $name,
            'slug'                  => $fieldData['slug'] ?? $slug,
            'aboutHtml'             => $about,
            'featuredImage'         => $featuredImage,
            'brandName'             => $brandName,
            'brandSlug'             => $brandSlug,
            'logo'                  => $brandLogo,
            'windowTypes'           => $windowTypes,
            'sidebarMaterialGroups' => $sidebarMaterialGroups,
            'collections'           => $collections,
            'collectionsTitle'      => $collectionsTitle,
            'heroFormHtml'          => $heroFormHtml,
            'seoTitle'              => $seoTitle,
            'seoDescription'        => $seoDescription,
            'ogTitle'               => $ogTitle,
            'ogDescription'         => $ogDescription,
            'ogImage'               => $ogImage,
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

        $name            = $fieldData['name'] ?? 'Collection';
        $longDescription = $fieldData['long-description'] ?? $collection->wf_long_description ?? '';
        $priceCategory   = $fieldData['price-category'] ?? '';
        $material        = $fieldData['material'] ?? $collection->wf_material ?? '';

        // Featured / hero background image
        $featuredImage = null;
        $wfFeaturedImg = $collection->wf_featured_image;
        if (is_array($wfFeaturedImg) && isset($wfFeaturedImg['url'])) {
            $featuredImage = $wfFeaturedImg['url'];
        } else {
            $featuredImage = $this->extractImageUrl($fieldData, ['featured-image', 'property-listing---featured-image']);
        }

        $aboutImage = $this->extractImageUrl($fieldData, ['property-type---icon']);
        if ($aboutImage === null) {
            $aboutImage = $featuredImage;
        }

        // Parent brand
        $parentBrand = $collection->webflowReference('parent-brand');
        $brandName = '';
        $brandSlug = '';
        $brandLogo = null;
        $brandLogoSvg = null;
        $sidebarMaterialGroups = collect();
        if ($parentBrand) {
            $brandFd   = is_array($parentBrand->field_data) ? $parentBrand->field_data : [];
            $brandName = $brandFd['name'] ?? '';
            $brandSlug = $brandFd['slug'] ?? '';
            $brandLogo = $this->extractImageUrl($brandFd, ['brand-logo', 'agent-avatar-photo']);
            $brandLogoSvg = $this->extractImageUrl($brandFd, ['logo-svg', 'brand-logo']);
            $sidebarMaterialGroups = $this->buildBrandSidebarMaterialGroups($parentBrand, $brandFd);
        }

        // Collections tab details – window types, glass, colors, options etc.
        $tabDetails = $this->resolveBrandCollectionTabItems($collection)
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

        $glassAll = $tabDetails->filter(fn ($t) => str_contains($t['category'], 'glass'));
        $standardGlass = $glassAll->filter(fn ($t) => $t['subcategory'] === '' || stripos($t['subcategory'], 'standard') !== false);
        $tintedGlass   = $glassAll->filter(fn ($t) => stripos($t['subcategory'], 'tinted') !== false);
        $obscureGlass  = $glassAll->filter(fn ($t) => stripos($t['subcategory'], 'obscure') !== false);
        $hasGlassTab   = $glassAll->isNotEmpty();

        $colorItems     = $tabDetails->filter(fn ($t) => str_contains($t['category'], 'color'));
        $exteriorColors = $colorItems->filter(fn ($t) => stripos($t['subcategory'], 'exterior') !== false);
        $interiorColors = $colorItems->filter(fn ($t) => stripos($t['subcategory'], 'interior') !== false);

        $gridStyles    = $tabDetails->filter(fn ($t) => str_contains($t['category'], 'grid style'));
        $gridPatterns  = $tabDetails->filter(fn ($t) => str_contains($t['category'], 'grid pattern'));
        $gridPatternImages   = $gridPatterns->filter(fn ($t) => ! empty($t['picture']));
        $gridPatternSwatches = $gridPatterns->filter(fn ($t) => empty($t['picture']) && ($t['color'] ?? '') !== '');
        $hardwareItems = $tabDetails->filter(fn ($t) => str_contains($t['category'], 'hardware'));
        $hasOptionsTab = $gridStyles->isNotEmpty() || $gridPatterns->isNotEmpty() || $hardwareItems->isNotEmpty();

        $configurationSizes = (bool) ($collection->wf_configuration_sizes ?? $fieldData['configuration-sizes'] ?? false);
        $configurationSizesDescription = trim((string) ($collection->wf_configuration_sizes_description ?? $fieldData['configuration-sizes-description'] ?? ''));
        $performance = (bool) ($collection->wf_performance ?? $fieldData['performance'] ?? false);
        $performanceDescription = trim((string) ($collection->wf_performance_description ?? $fieldData['performance-description'] ?? ''));

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

        $heroFormHtml = $this->resolveCollectionHeroPricing($collection, $fieldData);

        $seoTitle       = $fieldData['seo-title']             ?? $name;
        $seoDescription = $fieldData['seo-description']       ?? '';
        $ogTitle        = $fieldData['opengraph-title']        ?? $seoTitle;
        $ogDescription  = $fieldData['opengraph-description'] ?? $seoDescription;
        $ogImage        = $fieldData['opengraph-image']        ?? $featuredImage ?? '';

        return view('brand-collections.show', [
            'fieldData'               => $fieldData,
            'name'                    => $name,
            'slug'                    => $fieldData['slug'] ?? $slug,
            'longDescription'         => $longDescription,
            'priceCategory'           => $priceCategory,
            'material'                => $material,
            'featuredImage'           => $featuredImage,
            'aboutImage'              => $aboutImage,
            'brandName'               => $brandName,
            'brandSlug'               => $brandSlug,
            'brandLogo'               => $brandLogo,
            'brandLogoSvg'            => $brandLogoSvg,
            'sidebarMaterialGroups'   => $sidebarMaterialGroups,
            'windowTypes'             => $windowTypes,
            'standardGlass'           => $standardGlass,
            'tintedGlass'             => $tintedGlass,
            'obscureGlass'            => $obscureGlass,
            'hasGlassTab'             => $hasGlassTab,
            'exteriorColors'          => $exteriorColors,
            'interiorColors'          => $interiorColors,
            'gridStyles'              => $gridStyles,
            'gridPatterns'            => $gridPatterns,
            'gridPatternImages'       => $gridPatternImages,
            'gridPatternSwatches'     => $gridPatternSwatches,
            'hardwareItems'           => $hardwareItems,
            'hasOptionsTab'           => $hasOptionsTab,
            'configurationSizes'      => $configurationSizes,
            'configurationSizesDescription' => $configurationSizesDescription,
            'performance'             => $performance,
            'performanceDescription'  => $performanceDescription,
            'advantages'              => $advantages,
            'inspirationPhotos'       => $inspirationPhotos,
            'aboutDescription'        => $aboutDescription,
            'aboutHtml'               => $aboutHtml,
            'heroFormHtml'            => $heroFormHtml,
            'webflowCollectionId'     => '69366118c296b5e2e8bdbfb2',
            'seoTitle'                => $seoTitle,
            'seoDescription'          => $seoDescription,
            'ogTitle'                 => $ogTitle,
            'ogDescription'           => $ogDescription,
            'ogImage'                 => $ogImage,
        ]);
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
                        'name'      => $fd['name'] ?? '',
                        'slug'      => $postSlug,
                        'image'     => $this->extractImageUrl($fd, ['main-project-image', 'client-logo']) ?? '',
                        'published' => $this->formatBlogPublishedDate($post->webflow_published_on),
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
                        'name'      => $fd['name'] ?? '',
                        'slug'      => $postSlug,
                        'image'     => $this->extractImageUrl($fd, ['main-project-image', 'client-logo']) ?? '',
                        'published' => $this->formatBlogPublishedDate($item['lastPublished'] ?? null),
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

    public function countyHubBySlug(string $slug)
    {
        $slug = strtolower(trim($slug));
        $fieldData = $this->findCountyHubFieldData($slug);

        abort_if(! is_array($fieldData), 404);

        $countyName = $fieldData['county-name'] ?? $fieldData['name'] ?? 'Bay Area';
        $metaTitle = $fieldData['meta-title'] ?? "{$countyName} Window Replacement | Deluxe Windows";
        $metaDescription = $fieldData['meta-description'] ?? '';
        $heroImage = $this->extractImageUrl($fieldData, ['hero-image']) ?? '';
        $countyIntro = $fieldData['county-intro'] ?? '';
        $cities = $this->resolveCountyHubCities($fieldData);

        return view('county-hub-pages.show', compact(
            'slug',
            'countyName',
            'metaTitle',
            'metaDescription',
            'heroImage',
            'countyIntro',
            'cities',
        ));
    }

    public function windowReplacementBySlug(string $slug)
    {
        $slug = strtolower(trim($slug));
        $fieldData = $this->findWindowReplacementFieldData($slug);

        abort_if(! is_array($fieldData), 404);

        $cityName = $fieldData['city-name'] ?? $fieldData['name'] ?? '';
        $cityLabel = $cityName !== '' ? "{$cityName}, CA" : 'Bay Area, CA';
        $countyName = $fieldData['county'] ?? '';
        $metaTitle = $fieldData['meta-title'] ?? "{$cityName} Window Replacement | Deluxe Windows";
        $metaDescription = $fieldData['meta-description'] ?? '';
        $heroImage = $this->extractImageUrl($fieldData, ['hero-image', 'og-image']) ?? '';
        $paragraph1 = $fieldData['city-paragraph-1'] ?? '';
        $paragraph2 = $fieldData['city-paragraph-2'] ?? '';
        $faqs = $this->resolveServiceAreaFaqs($fieldData);
        $windowTypes = $this->loadServiceAreaWindowTypes();
        $featuredBrands = $this->resolveServiceAreaFeaturedBrands($fieldData);
        $countyHubSlug = $this->resolveCountyHubSlug($fieldData['county-page'] ?? null);
        $schemaScripts = $this->buildServiceAreaSchemaScripts($fieldData, $cityName, $slug, $faqs, $metaDescription);

        return view('window-replacement.show', compact(
            'slug',
            'cityName',
            'cityLabel',
            'countyName',
            'metaTitle',
            'metaDescription',
            'heroImage',
            'paragraph1',
            'paragraph2',
            'faqs',
            'windowTypes',
            'featuredBrands',
            'countyHubSlug',
            'schemaScripts',
        ));
    }

    private function findWindowReplacementFieldData(string $slug): ?array
    {
        try {
            $item = WindowReplacementWebflowItem::query()
                ->where('is_archived', false)
                ->where('is_draft', false)
                ->where(function ($query) use ($slug) {
                    $query->where('field_data->city-slug', $slug)
                        ->orWhere('field_data->slug', $slug);
                })
                ->orderByDesc('id')
                ->first();

            if ($item) {
                return is_array($item->field_data) ? $item->field_data : null;
            }
        } catch (\Throwable) {
            // Table may be missing on staging — fall back to import JSON.
        }

        foreach ($this->loadWindowReplacementImportItems() as $importItem) {
            if (($importItem['isDraft'] ?? false) || ($importItem['isArchived'] ?? false)) {
                continue;
            }

            $fd = is_array($importItem['fieldData'] ?? null) ? $importItem['fieldData'] : [];
            if (($fd['city-slug'] ?? '') === $slug || ($fd['slug'] ?? '') === $slug) {
                return $fd;
            }
        }

        return null;
    }

    /**
     * @return list<array{question: string, answer: string, answer_plain: string}>
     */
    private function resolveServiceAreaFaqs(array $fieldData): array
    {
        $faqs = [];

        for ($i = 1; $i <= 5; $i++) {
            $question = trim((string) ($fieldData["faq-{$i}-question"] ?? ''));
            if ($question === '') {
                continue;
            }

            $answerHtml = (string) ($fieldData["faq-{$i}-answer"] ?? '');
            $answerPlain = trim((string) ($fieldData["faq-{$i}-answer-plain-text"] ?? ''));

            $faqs[] = [
                'question'     => $question,
                'answer'       => $answerHtml !== '' ? $answerHtml : '<p>'.e($answerPlain).'</p>',
                'answer_plain' => $answerPlain,
            ];
        }

        return $faqs;
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{name: string, slug: string, image: string}>
     */
    private function loadServiceAreaWindowTypes(): \Illuminate\Support\Collection
    {
        $slugs = array_slice(self::WINDOWS_INDEX_SLUG_ORDER, 0, 6);

        try {
            return WindowsWebflowItem::query()
                ->where('is_archived', false)
                ->where('is_draft', false)
                ->get()
                ->filter(function ($item) use ($slugs) {
                    $itemSlug = (string) data_get($item->field_data, 'slug', '');

                    return in_array($itemSlug, $slugs, true);
                })
                ->sortBy(function ($item) use ($slugs) {
                    $itemSlug = (string) data_get($item->field_data, 'slug', '');
                    $pos = array_search($itemSlug, $slugs, true);

                    return $pos === false ? 999 : $pos;
                })
                ->map(fn ($item) => $this->mapWindowsIndexCard($item))
                ->filter(fn ($card) => ($card['slug'] ?? '') !== '')
                ->values();
        } catch (\Throwable) {
            return collect();
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{name: string, slug: string, logo: string}>
     */
    private function resolveServiceAreaFeaturedBrands(array $fieldData): \Illuminate\Support\Collection
    {
        $brandIds = $fieldData['featured-brands'] ?? [];
        if (! is_array($brandIds) || $brandIds === []) {
            return collect();
        }

        $brands = collect();

        try {
            $items = BrandsWebflowItem::query()
                ->whereIn('webflow_item_id', $brandIds)
                ->where('is_archived', false)
                ->where('is_draft', false)
                ->get()
                ->keyBy('webflow_item_id');

            foreach ($brandIds as $brandId) {
                $item = $items->get($brandId);
                if ($item === null) {
                    continue;
                }

                $card = $this->mapBrandIndexCard($item);
                if (($card['slug'] ?? '') !== '') {
                    $brands->push([
                        'name' => $card['name'],
                        'slug' => $card['slug'],
                        'logo' => $card['logo'],
                    ]);
                }
            }
        } catch (\Throwable) {
            $brands = collect();
        }

        if ($brands->isNotEmpty()) {
            return $brands;
        }

        foreach ($this->loadBrandsImportItems() as $importItem) {
            if (($importItem['isDraft'] ?? false) || ($importItem['isArchived'] ?? false)) {
                continue;
            }

            $id = $importItem['id'] ?? '';
            if (! in_array($id, $brandIds, true)) {
                continue;
            }

            $fd = is_array($importItem['fieldData'] ?? null) ? $importItem['fieldData'] : [];
            $brandSlug = $fd['slug'] ?? '';
            if ($brandSlug === '') {
                continue;
            }

            $brands->push([
                'name' => $fd['name'] ?? '',
                'slug' => $brandSlug,
                'logo' => $this->extractImageUrl($fd, ['logo-svg', 'brand-logo', 'agent---avatar-photo']) ?? '',
            ]);
        }

        return $brands->values();
    }

    private function resolveCountyHubSlug(mixed $countyPageId): ?string
    {
        if (! is_string($countyPageId) || $countyPageId === '') {
            return null;
        }

        try {
            $item = CountyHubPagesWebflowItem::query()
                ->where('webflow_item_id', $countyPageId)
                ->first();

            if ($item) {
                $fd = is_array($item->field_data) ? $item->field_data : [];

                return $fd['county-slug'] ?? $fd['slug'] ?? null;
            }
        } catch (\Throwable) {
            // Fall back to import JSON.
        }

        foreach ($this->loadCountyHubImportItems() as $importItem) {
            if (($importItem['id'] ?? '') !== $countyPageId) {
                continue;
            }

            $fd = is_array($importItem['fieldData'] ?? null) ? $importItem['fieldData'] : [];

            return $fd['county-slug'] ?? $fd['slug'] ?? null;
        }

        return null;
    }

    /**
     * @param  list<array{question: string, answer: string, answer_plain: string}>  $faqs
     * @return list<string>
     */
    private function buildServiceAreaSchemaScripts(
        array $fieldData,
        string $cityName,
        string $slug,
        array $faqs,
        string $metaDescription,
    ): array {
        $custom = trim((string) ($fieldData['schema-json'] ?? ''));
        if ($custom !== '') {
            return [$custom];
        }

        $cityLabel = "{$cityName}, CA";
        $scripts = [];

        $scripts[] = json_encode([
            '@context'    => 'https://schema.org',
            '@type'       => 'Service',
            'name'        => "Window Replacement in {$cityLabel}",
            'provider'    => [
                '@type'     => 'HomeAndConstructionBusiness',
                'name'      => 'Deluxe Windows',
                'telephone' => site_phone_tel(),
                'url'       => 'https://www.deluxewindows.com',
            ],
            'areaServed'  => [
                '@type'            => 'City',
                'name'             => $cityName,
                'containedInPlace' => [
                    '@type' => 'State',
                    'name'  => 'California',
                ],
            ],
            'serviceType' => 'Window Replacement',
            'description' => $metaDescription !== ''
                ? $metaDescription
                : "Professional window and door replacement in {$cityLabel}.",
            'offers'      => [
                '@type'         => 'Offer',
                'name'          => 'Free Quote',
                'price'         => '0',
                'priceCurrency' => 'USD',
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($faqs !== []) {
            $mainEntity = [];
            foreach ($faqs as $faq) {
                $answerText = $faq['answer_plain'] !== ''
                    ? $faq['answer_plain']
                    : trim(strip_tags($faq['answer']));

                if ($answerText === '') {
                    continue;
                }

                $mainEntity[] = [
                    '@type'          => 'Question',
                    'name'           => $faq['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => $answerText,
                    ],
                ];
            }

            if ($mainEntity !== []) {
                $scripts[] = json_encode([
                    '@context'   => 'https://schema.org',
                    '@type'      => 'FAQPage',
                    'mainEntity' => $mainEntity,
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        }

        return $scripts;
    }

    private function loadBrandsImportItems(): array
    {
        $path = base_path('webflow-data/current/collections/brands/items.json');
        if (! File::exists($path)) {
            return [];
        }

        $payload = json_decode((string) File::get($path), true);
        $items = $payload['items'] ?? [];

        return is_array($items) ? $items : [];
    }

    private function findCountyHubFieldData(string $slug): ?array
    {
        try {
            $item = CountyHubPagesWebflowItem::query()
                ->where('is_archived', false)
                ->where('is_draft', false)
                ->where(function ($query) use ($slug) {
                    $query->where('field_data->slug', $slug)
                        ->orWhere('field_data->county-slug', $slug);
                })
                ->orderByDesc('id')
                ->first();

            if ($item) {
                return is_array($item->field_data) ? $item->field_data : null;
            }
        } catch (\Throwable) {
            // Table may be missing on staging — fall back to import JSON.
        }

        foreach ($this->loadCountyHubImportItems() as $importItem) {
            $fd = is_array($importItem['fieldData'] ?? null) ? $importItem['fieldData'] : [];
            if (($fd['slug'] ?? '') === $slug || ($fd['county-slug'] ?? '') === $slug) {
                return $fd;
            }
        }

        return null;
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{name: string, slug: string}>
     */
    private function resolveCountyHubCities(array $fieldData): \Illuminate\Support\Collection
    {
        $cityIds = $fieldData['cities-in-county'] ?? [];
        if (! is_array($cityIds) || $cityIds === []) {
            return collect();
        }

        $ordered = collect();

        try {
            $items = WindowReplacementWebflowItem::query()
                ->whereIn('webflow_item_id', $cityIds)
                ->where('is_archived', false)
                ->where('is_draft', false)
                ->get()
                ->keyBy('webflow_item_id');

            foreach ($cityIds as $cityId) {
                $item = $items->get($cityId);
                if ($item === null) {
                    continue;
                }

                $fd = is_array($item->field_data) ? $item->field_data : [];
                $citySlug = $fd['city-slug'] ?? $fd['slug'] ?? '';
                $cityName = $fd['city-name'] ?? $fd['name'] ?? '';

                if ($citySlug !== '' && $cityName !== '') {
                    $ordered->push([
                        'name' => $cityName,
                        'slug' => $citySlug,
                    ]);
                }
            }
        } catch (\Throwable) {
            $ordered = collect();
        }

        if ($ordered->isNotEmpty()) {
            return $ordered;
        }

        $importItems = collect($this->loadWindowReplacementImportItems())->keyBy('id');

        foreach ($cityIds as $cityId) {
            $importItem = $importItems->get($cityId);
            if ($importItem === null) {
                continue;
            }

            $fd = is_array($importItem['fieldData'] ?? null) ? $importItem['fieldData'] : [];
            $citySlug = $fd['city-slug'] ?? $fd['slug'] ?? '';
            $cityName = $fd['city-name'] ?? $fd['name'] ?? '';

            if ($citySlug !== '' && $cityName !== '') {
                $ordered->push([
                    'name' => $cityName,
                    'slug' => $citySlug,
                ]);
            }
        }

        return $ordered;
    }

    private function loadCountyHubImportItems(): array
    {
        $path = base_path('webflow-data/current/imports/county-hub-pages.json');
        if (! File::exists($path)) {
            $path = base_path('webflow-data/current/collections/county-hub-pages/items.json');
        }

        if (! File::exists($path)) {
            return [];
        }

        $payload = json_decode((string) File::get($path), true);
        $items = $payload['items'] ?? [];

        return is_array($items) ? $items : [];
    }

    private function loadWindowReplacementImportItems(): array
    {
        $path = base_path('webflow-data/current/imports/window-replacement.json');
        if (! File::exists($path)) {
            return [];
        }

        $payload = json_decode((string) File::get($path), true);
        $items = $payload['items'] ?? [];

        return is_array($items) ? $items : [];
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

    /**
     * Sidebar material groups for brand pages (matches deluxewindows.com/brands/* layout).
     *
     * @return \Illuminate\Support\Collection<int, array{name: string, sublabel: ?string, collections: \Illuminate\Support\Collection, visible: bool, insertWindowTypesAfter: bool}>
     */
    private function buildBrandSidebarMaterialGroups(BrandsWebflowItem $brand, array $fieldData): \Illuminate\Support\Collection
    {
        $materialOrder = [
            'Aluminum Windows',
            'Fiberglass Windows',
            'Wood Clad Windows',
            'Wood Windows',
            'Aluminum Clad Windows',
            'Steel Windows',
            'Vinyl Windows',
        ];

        $materialIdsByName = WindowsWebflowItem::query()
            ->where('is_archived', false)
            ->where('is_draft', false)
            ->get()
            ->mapWithKeys(function ($item) {
                $fd = is_array($item->field_data) ? $item->field_data : [];
                $name = $fd['name'] ?? '';

                return $name !== '' ? [$name => $item->webflow_item_id] : [];
            });

        $brandCollections = BrandCollectionsWebflowItem::query()
            ->where('is_archived', false)
            ->where('is_draft', false)
            ->where('field_data->parent-brand', $brand->webflow_item_id)
            ->get()
            ->map(function ($collection) {
                $fd = is_array($collection->field_data) ? $collection->field_data : [];

                return [
                    'name'         => $fd['name'] ?? '',
                    'slug'         => $fd['slug'] ?? '',
                    'material'     => $fd['material'] ?? '',
                    'mainmaterial' => $fd['mainmaterial'] ?? null,
                    'materials'    => is_array($fd['materials'] ?? null) ? $fd['materials'] : [],
                    'image'        => $this->extractImageUrl($fd, ['property-type---icon', 'featured-image']) ?? '',
                ];
            })
            ->filter(fn ($c) => $c['name'] !== '' && $c['slug'] !== '')
            ->values();

        $showVinylSubcategories = (bool) ($fieldData['new-construction-replacement-categories'] ?? false);
        $groups = collect();
        $insertWindowTypesAfterFirst = true;

        foreach ($materialOrder as $materialName) {
            if ($materialName === 'Vinyl Windows' && $showVinylSubcategories) {
                $materialId = $materialIdsByName->get($materialName);
                $vinylCollections = $brandCollections
                    ->filter(fn ($c) => $this->brandCollectionMatchesMaterial($c, $materialName, $materialId))
                    ->values();

                foreach (['/ New Construction', '/ Replacement'] as $sublabel) {
                    $matched = $vinylCollections
                        ->filter(fn ($c) => $this->brandCollectionMatchesVinylSubcategory($c, $sublabel))
                        ->values();

                    $groups->push([
                        'name'                   => $materialName,
                        'sublabel'               => $sublabel,
                        'collections'            => $matched,
                        'visible'                => $matched->isNotEmpty(),
                        'insertWindowTypesAfter' => false,
                    ]);
                }

                if ($vinylCollections->isNotEmpty()) {
                    $insertWindowTypesAfterFirst = false;
                }

                continue;
            }

            $materialId = $materialIdsByName->get($materialName);
            $matched = $brandCollections
                ->filter(fn ($c) => $this->brandCollectionMatchesMaterial($c, $materialName, $materialId))
                ->values();

            $groups->push([
                'name'                   => $materialName,
                'sublabel'               => null,
                'collections'            => $matched,
                'visible'                => $matched->isNotEmpty(),
                'insertWindowTypesAfter' => $insertWindowTypesAfterFirst && $matched->isNotEmpty(),
            ]);

            if ($matched->isNotEmpty()) {
                $insertWindowTypesAfterFirst = false;
            }
        }

        return $groups;
    }

    /**
     * Resolve Collections Tab items for a brand collection.
     *
     * Webflow links tabs in two directions:
     * - brand-collections.collections-tabs-details → collections-tabs (explicit subset)
     * - collections-tabs.collections-new-template → brand-collections (full set)
     */
    private function resolveBrandCollectionTabItems(BrandCollectionsWebflowItem $collection): \Illuminate\Support\Collection
    {
        $collectionWebflowId = (string) ($collection->webflow_item_id ?? '');
        if ($collectionWebflowId === '') {
            return collect();
        }

        $directTabs = $collection->webflowReferences('collections-tabs-details')
            ->filter(fn ($tab) => ! ($tab->is_draft ?? false) && ! ($tab->is_archived ?? false));

        $reverseTabs = CollectionsTabsWebflowItem::query()
            ->where('is_archived', false)
            ->where('is_draft', false)
            ->where(function ($query) use ($collectionWebflowId) {
                $query->whereJsonContains('field_data->collections-new-template', $collectionWebflowId)
                    ->orWhereJsonContains('wf_collections_new_template', $collectionWebflowId);
            })
            ->get();

        $seen = [];
        $ordered = [];

        foreach ($directTabs as $tab) {
            $id = (string) ($tab->webflow_item_id ?? '');
            if ($id !== '' && ! isset($seen[$id])) {
                $seen[$id] = true;
                $ordered[] = $tab;
            }
        }

        foreach ($reverseTabs as $tab) {
            $id = (string) ($tab->webflow_item_id ?? '');
            if ($id !== '' && ! isset($seen[$id])) {
                $seen[$id] = true;
                $ordered[] = $tab;
            }
        }

        return collect($ordered);
    }

    private function resolveCollectionHeroPricing(BrandCollectionsWebflowItem $collection, array $fieldData): string
    {
        $seriesSlug = (string) ($fieldData['slug'] ?? '');
        $seriesPricing = app(PromotionControlService::class)->seriesPricing($seriesSlug);
        if ($seriesPricing !== null) {
            return app(PromotionControlService::class)->priceHtml(
                $seriesPricing['base'],
                $seriesPricing['final']
            );
        }

        $materialName = $fieldData['material'] ?? $collection->wf_material ?? '';

        if ($materialName !== '') {
            $material = WindowsWebflowItem::query()
                ->where('is_archived', false)
                ->where('is_draft', false)
                ->get()
                ->first(function ($item) use ($materialName) {
                    $fd = is_array($item->field_data) ? $item->field_data : [];

                    return strcasecmp($fd['name'] ?? '', $materialName) === 0;
                });

            if ($material) {
                $materialFd = is_array($material->field_data) ? $material->field_data : [];
                $discount   = $materialFd['discounttext'] ?? '';

                if (is_string($discount) && trim(strip_tags($discount)) !== '') {
                    return $this->legacyDiscountToPromoHtml($discount);
                }
            }
        }

        return app(PromotionControlService::class)->priceHtml('915', '$549');
    }

    private function resolveWindowTypeHeroPricing(WindowTypeWebflowItem $windowType, array $fieldData): string
    {
        $windowTypeSlug = (string) ($fieldData['slug'] ?? '');
        $windowTypePricing = app(PromotionControlService::class)->windowTypePricing($windowTypeSlug);
        if ($windowTypePricing !== null) {
            return app(PromotionControlService::class)->priceHtml(
                $windowTypePricing['base'],
                $windowTypePricing['final']
            );
        }

        $material = $windowType->webflowReference('windor-type-material');
        if ($material) {
            $materialFd = is_array($material->field_data) ? $material->field_data : [];
            $discount   = $materialFd['discounttext'] ?? '';

            if (is_string($discount) && trim(strip_tags($discount)) !== '') {
                return $this->legacyDiscountToPromoHtml($discount);
            }
        }

        return '<p>Starting from $1199 per window installed.</p><p><strong>Special pricing available upon request! </strong>‍</p>';
    }

    private function legacyDiscountToPromoHtml(string $legacyHtml, string $suffix = 'per window'): string
    {
        $legacyHtml = trim($legacyHtml);
        if ($legacyHtml === '') {
            return app(PromotionControlService::class)->priceHtml('915', '$549', $suffix);
        }

        if (preg_match('/<s>\s*\$?\s*([0-9][0-9,]*(?:\.[0-9]{1,2})?)\s*<\/s>/i', $legacyHtml, $baseMatch) !== 1) {
            return app(PromotionControlService::class)->priceHtml('915', '$549', $suffix);
        }

        if (preg_match('/<\/s>\s*\$?\s*([0-9][0-9,]*(?:\.[0-9]{1,2})?)/i', $legacyHtml, $finalMatch) !== 1) {
            return app(PromotionControlService::class)->priceHtml('915', '$549', $suffix);
        }

        return app(PromotionControlService::class)->priceHtml($baseMatch[1], $finalMatch[1], $suffix);
    }

    /**
     * Brand price priority:
     * 1) explicit brand override in Promotions tab
     * 2) inherited from first linked Windows-type (materials relation)
     *
     * @return array{base: string, final: string}|null
     */
    private function resolveBrandPromotionPricing(
        BrandsWebflowItem $brand,
        string $brandSlug,
        PromotionControlService $controls,
    ): ?array {
        $override = $controls->brandPricing($brandSlug);
        if ($override !== null) {
            return $override;
        }

        // Default brand pricing source: linked Windows main type.
        $mainType = $brand->webflowReference('windowmaintype');
        if ($mainType instanceof WindowsWebflowItem) {
            $mainTypeFd = is_array($mainType->field_data) ? $mainType->field_data : [];
            $mainTypeSlug = trim((string) ($mainTypeFd['slug'] ?? ''));
            if ($mainTypeSlug !== '') {
                $inherited = $controls->windowTypePricing($mainTypeSlug);
                if ($inherited !== null) {
                    return $inherited;
                }
            }
        }

        // Defensive fallback when reference resolver is unavailable.
        $mainTypeId = trim((string) (is_array($brand->field_data) ? ($brand->field_data['windowmaintype'] ?? '') : ''));
        if ($mainTypeId !== '') {
            $mainTypeRow = WindowsWebflowItem::query()
                ->where('webflow_item_id', $mainTypeId)
                ->orWhere('field_data->slug', $mainTypeId)
                ->first();
            if ($mainTypeRow instanceof WindowsWebflowItem) {
                $mainTypeFd = is_array($mainTypeRow->field_data) ? $mainTypeRow->field_data : [];
                $mainTypeSlug = trim((string) ($mainTypeFd['slug'] ?? ''));
                if ($mainTypeSlug !== '') {
                    $inherited = $controls->windowTypePricing($mainTypeSlug);
                    if ($inherited !== null) {
                        return $inherited;
                    }
                }
            }
        }

        // Legacy fallback for older links that still rely on materials references.
        foreach ($brand->webflowReferences('materials') as $material) {
            $fd = is_array($material->field_data) ? $material->field_data : [];
            $materialSlug = trim((string) ($fd['slug'] ?? ''));
            if ($materialSlug === '') {
                continue;
            }
            $inherited = $controls->windowTypePricing($materialSlug);
            if ($inherited !== null) {
                return $inherited;
            }
        }

        return null;
    }

    private function brandCollectionMatchesMaterial(array $collection, string $materialName, ?string $materialWebflowId): bool
    {
        if ($materialWebflowId !== null) {
            if (in_array($materialWebflowId, $collection['materials'], true)) {
                return true;
            }

            if ($collection['mainmaterial'] === $materialWebflowId) {
                return true;
            }
        }

        return strcasecmp($collection['material'], $materialName) === 0;
    }

    /**
     * Match vinyl subcategory groups (New Construction / Replacement) via plain-text Material field.
     */
    private function brandCollectionMatchesVinylSubcategory(array $collection, string $sublabel): bool
    {
        $subtype = $this->normalizeBrandCollectionMaterialSubtype($collection['material'] ?? '');
        if ($subtype === null) {
            return false;
        }

        $expectedSubtype = trim(str_replace('/', '', $sublabel));

        return strcasecmp($subtype, $expectedSubtype) === 0;
    }

    private function normalizeBrandCollectionMaterialSubtype(string $material): ?string
    {
        $normalized = strtolower(trim($material));

        if ($normalized === 'replacement') {
            return 'Replacement';
        }

        if (in_array($normalized, ['new construction', 'new construcion'], true)) {
            return 'New Construction';
        }

        return null;
    }

    private function mapWindowsIndexCard(WindowsWebflowItem $window): array
    {
        $fd = is_array($window->field_data) ? $window->field_data : [];

        $image = $this->extractImageUrl($fd, [
            'property-listing---featured-image',
            'property-listing---thumbnail-image-v1',
        ]);

        return [
            'name'  => $fd['name'] ?? '',
            'slug'  => $fd['slug'] ?? '',
            'image' => $image ?? '',
        ];
    }

    private function mapBrandIndexCard(BrandsWebflowItem $brand): array
    {
        $fd = is_array($brand->field_data) ? $brand->field_data : [];

        $logo = $this->extractImageUrl($fd, ['logo-svg', 'brand-logo', 'agent---avatar-photo']);

        $materials = $brand->webflowReferences('materials')
            ->map(function ($material) {
                $mfd = is_array($material->field_data) ? $material->field_data : [];
                $name = $mfd['name'] ?? '';
                $slug = $mfd['slug'] ?? '';

                if ($name === '' || $slug === '') {
                    return null;
                }

                return [
                    'name'         => $name,
                    'slug'         => $slug,
                    'filter_value' => $this->brandMaterialFilterValue($name),
                ];
            })
            ->filter()
            ->values()
            ->all();

        $priceSlots = [
            ['field' => 'price1', 'label' => '$',     'active' => (bool) ($fd['field-5'] ?? false)],
            ['field' => 'price2', 'label' => '$$',    'active' => (bool) ($fd['field-2'] ?? false)],
            ['field' => 'price3', 'label' => '$$$',   'active' => (bool) ($fd['field-3'] ?? false)],
            ['field' => 'price4', 'label' => '$$$$',  'active' => (bool) ($fd['field-4'] ?? false)],
            ['field' => 'price5', 'label' => '$$$$$', 'active' => (bool) ($fd['field'] ?? false)],
        ];

        return [
            'name'        => $fd['name'] ?? '',
            'slug'        => $fd['slug'] ?? '',
            'logo'        => $logo ?? '',
            'materials'   => $materials,
            'price_range' => $fd['price-range'] ?? '',
            'price_slots' => $priceSlots,
            'features'    => [
                ['title' => 'KEY FEATURES',       'text' => $fd['brand-feature-description-1'] ?? ''],
                ['title' => 'Energy Efficiency',  'text' => $fd['brand-feature-description-2'] ?? ''],
                ['title' => 'Sound Insulation',   'text' => $fd['brand-feature-description-3'] ?? ''],
                ['title' => 'Warranty',           'text' => $fd['brand-feature-description-4'] ?? ''],
            ],
        ];
    }

    private function brandMaterialFilterValue(string $materialName): string
    {
        $short = preg_replace('/\s+Windows$/i', '', trim($materialName));

        return is_string($short) && $short !== '' ? $short : $materialName;
    }
}

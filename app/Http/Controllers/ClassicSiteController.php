<?php

namespace App\Http\Controllers;

use App\Models\DoorBrand;
use App\Models\Lead;
use App\Models\Webflow\BlogWebflowItem;
use App\Models\Webflow\BrandCollectionsWebflowItem;
use App\Models\Webflow\BrandsWebflowItem;
use App\Models\Webflow\CollectionsTabsWebflowItem;
use App\Models\Webflow\CountyHubPagesWebflowItem;
use App\Models\Webflow\DoorsWebflowItem;
use App\Models\Webflow\DoorTypesWebflowItem;
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
            ?? $this->templateWindowTypeHeroImageUrl((string) ($fieldData['slug'] ?? $slug))
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

        $controls       = app(PromotionControlService::class);
        $windowPricing  = $controls->windowTypePricing(
            (string) ($window->webflow_item_id ?? ''),
            (string) ($fieldData['slug'] ?? $slug)
        );
        $discountHtml   = $windowPricing
            ? $controls->pricingHtmlFromMap($windowPricing, 'per window installed')
            : $this->legacyDiscountToPromoHtml((string) ($fieldData['discounttext'] ?? ''), 'per window installed');
        $pagePromotionAvailable = $windowPricing !== null
            || trim(strip_tags((string) ($fieldData['discounttext'] ?? ''))) !== '';

        return view('windows.show', [
            'windowFieldData'  => $fieldData,
            'title'            => $fieldData['name'] ?? 'Window',
            'summary'          => $fieldData['property-listing---summary'] ?? '',
            'aboutHtml'        => $fieldData['property-listing---about'] ?? '',
            'discountHtml'     => $discountHtml,
            'pagePromotionAvailable' => $pagePromotionAvailable,
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

        $heroImage = $door->customHeroImageUrl()
            ?? $this->templateDoorTypeHeroImageUrl((string) ($fieldData['slug'] ?? $slug))
            ?? $this->extractImageUrl($fieldData, [
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

        $controls    = app(PromotionControlService::class);
        $doorPricing = $controls->doorPricing(
            (string) ($door->webflow_item_id ?? ''),
            (string) ($fieldData['slug'] ?? $slug)
        );
        $discountHtml = $doorPricing
            ? $controls->pricingHtmlFromMap($doorPricing, 'per door installed')
            : $this->legacyDiscountToPromoHtml(
                (string) ($fieldData['door-discount'] ?? $door->wf_door_discount ?? ''),
                'per door installed'
            );
        $pagePromotionAvailable = $doorPricing !== null
            || trim(strip_tags((string) ($fieldData['door-discount'] ?? $door->wf_door_discount ?? ''))) !== '';

        return view('doors.show', [
            'doorFieldData'  => $fieldData,
            'title'          => $fieldData['name'] ?? 'Door',
            'slug'           => $fieldData['slug'] ?? $slug,
            'summary'        => $fieldData['description'] ?? '',
            'aboutHtml'      => $fieldData['blog-post---rich-text'] ?? $door->wf_blog_post_rich_text ?? '',
            'discountHtml'   => $discountHtml,
            'pagePromotionAvailable' => $pagePromotionAvailable,
            'doorHeroFormHtml'       => $discountHtml,
            'doorPromotionPricing'   => $doorPricing,
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
            '/webflow-assets/images/68c89a4ddd537cbd43140679_IMG_8948-copy.jpg',
            '/webflow-assets/images/68c89a099acdcf47011a2c81_IMG_9092-copy.jpg',
            '/webflow-assets/images/68c899e85dd261dc57e23f89_IMG_8912-copy.jpg',
            '/webflow-assets/images/68c899b77158e4af305d0b2f_IMG_0875-copy.jpg',
            '/webflow-assets/images/68c89985de144a394c6a6916_IMG_0881-copy.jpg',
            '/webflow-assets/images/68c89963ee9a56539d6de1d5_IMG_0864-copy.jpg',
            '/webflow-assets/images/68c89943bd238c5a7b66bf4a_IMG_0867-copy.jpg',
            '/webflow-assets/images/68c8992fc7e78105e5aed93a_IMG_0227-copy.jpg',
            '/webflow-assets/images/68c8990ca76a3466d850947a_IMG_4081-copy.jpg',
            '/webflow-assets/images/68c898e282004723ddf0b3fc_IMG_0466-copy.jpg',
            '/webflow-assets/images/68c898855be0d592d5a173dd_IMG_0202-copy.jpg',
            '/webflow-assets/images/68c898137597540c9cac63f5_IMG_0195-copy.jpg',
            '/webflow-assets/images/68c897fc1177ae25483e8e48_IMG_0112-copy.jpg',
            '/webflow-assets/images/68c897d4b7a2ebaf99e490f4_IMG_0069-copy.jpg',
            '/webflow-assets/images/68c897af834d882b753a94f9_IMG_0059-copy.jpg',
            '/webflow-assets/images/68c8977c397242cfdcdaeb50_IMG_0057-copy.jpg',
            '/webflow-assets/images/68c89741737d1ddcf559e0fb_IMG_0053-copy.jpg',
            '/webflow-assets/images/68c8972e6843f4d623e8f462_IMG_0049-copy.jpg',
            '/webflow-assets/images/68c8971b3c012bcb583f91fe_IMG_0041-copy.jpg',
            '/webflow-assets/images/68c8933647d708de3996bae0_IMG_0432-copy.jpg',
            '/webflow-assets/images/68c892f96843f4d623e81a2e_IMG_0011-copy.jpg',
            '/webflow-assets/images/68c892acb4d1c57c203a9a9b_IMG_0548-copy.jpg',
            '/webflow-assets/images/68c8927034755704ad6441a7_IMG_0603-copy.jpg',
            '/webflow-assets/images/68c892543e72481766a5c610_IMG_1249-copy.jpg',
            '/webflow-assets/images/68c89209b083bfab499d45c0_IMG_1257-copy.jpg',
            '/webflow-assets/images/68c891f682004723ddef6569_IMG_1260-copy.jpg',
            '/webflow-assets/images/68c891e34c1a8f473324b118_IMG_2224-copy.jpg',
            '/webflow-assets/images/68c890599c7bd341f08e4a3e_IMG_0678-copy.jpg',
            '/webflow-assets/images/68c88f7d8fd58d9f978a8f92_IMG_1346-copy.jpg',
            '/webflow-assets/images/68c88f6d51743ff7b142dce7_IMG_1322-copy.jpg',
            '/webflow-assets/images/68c88eecc7e78105e5aca538_IMG_1496-copy.jpg',
            '/webflow-assets/images/68c88cb9172c5f771dd0b902_IMG_0619-copy.jpg',
            '/webflow-assets/images/68c88cac917e7e752c2ffb0f_IMG_3939-copy.jpg',
            '/webflow-assets/images/68c88c075e6614fb046428bc_IMG_0909-copy.jpg',
            '/webflow-assets/images/68c88bc34026fdb7f2598697_IMG_0908-copy.jpg',
            '/webflow-assets/images/68c88b898cf0554bdb23ec35_IMG_0900-copy.jpg',
            '/webflow-assets/images/68c88b62d0fc7ffcd1764a97_IMG_0896-copy.jpg',
            '/webflow-assets/images/68c88a4c7ee80cba98b9796b_IMG_2506-copy.jpg',
            '/webflow-assets/images/68c88a36a9eb08d6b08c5162_IMG_2529-copy.jpg',
            '/webflow-assets/images/68c88a22635ff53382a342f7_IMG_2533-copy.jpg',
            '/webflow-assets/images/68c889e63e44d8a594d7ee1a_IMG_0261-copy-2.jpg',
            '/webflow-assets/images/68c889a76859f9f8e2febe65_IMG_2491-copy.jpg',
            '/webflow-assets/images/68c888a737c1e1c51e1346dc_IMG_3001-copy.jpg',
            '/webflow-assets/images/68c88805a09627aa7a309b99_IMG_0862-copy.jpg',
            '/webflow-assets/images/68c887f10ee1341634cfefea_IMG_0857-copy.jpg',
            '/webflow-assets/images/68c887ac610f2fa8b924f4a5_IMG_0855-copy.jpg',
            '/webflow-assets/images/68c887905c2c53050ea7dfb5_IMG_0842-copy.jpg',
            '/webflow-assets/images/68c8877bfa1bc257940e66a1_IMG_0840-copy.jpg',
            '/webflow-assets/images/68c8832d032f5f6e5efd62be_IMG_1956-copy1.jpg',
            '/webflow-assets/images/68c88208966e1d20865bb516_824-Huntley-Dr---virtuallyherestudios.com-93-copy.jpg',
            '/webflow-assets/images/68c881be6a76eafd63b8b0fa_824-Huntley-Dr---virtuallyherestudios.com-56-copy.jpg',
            '/webflow-assets/images/68c880b868caf48c1916127f_824-Huntley-Dr---virtuallyherestudios.com-46-copy.jpg',
            '/webflow-assets/images/68c880613977a2ff65c0f598_824-Huntley-Dr---virtuallyherestudios.com-14-copy.jpg',
            '/webflow-assets/images/68c8801eb7a2ebaf99deca5c_824-Huntley-Dr---virtuallyherestudios.com-4-copy.jpg',
            '/webflow-assets/images/68c87f7765e49fa4f848897c_IMG_2604-copy.jpg',
            '/webflow-assets/images/68c87f1bdcf34a64421d75fe_3-copy.jpg',
            '/webflow-assets/images/68c87e439a25e87eb449765a_2-copy.jpg',
            '/webflow-assets/images/68c1e4da87641ae1b6c8388f_ML82019061_2_0.jpg',
            '/webflow-assets/images/68c1e3aec656444714174803_ML82019061_6_0-copy.jpg',
            '/webflow-assets/images/68c1e3014c208aa0190f3a32_ML82019061_14_0.jpg',
            '/webflow-assets/images/68c1e2c239323cddc951b404_ML82019061_5_0.jpg',
            '/webflow-assets/images/68c1e26dd8be9f9d0a5a66e5_IMG_0048-copy.jpg',
            '/webflow-assets/images/68c1e18e291dd5544d66a4db_IMG_0045-copy.jpg',
            '/webflow-assets/images/68c1e11d5175ebd3c6935a09_IMG_2921-copy1.jpg',
            '/webflow-assets/images/68c1e003616a2d64c36f65fa_IMG_4488-copy.jpg',
            '/webflow-assets/images/68c1dffa190335c0d8ed49a7_IMG_2925-copy.jpg',
            '/webflow-assets/images/688381ac192ac8f1f8a72d92_7.jpg',
            '/webflow-assets/images/6883819d9411c98ed1087590_1.jpg',
            '/webflow-assets/images/6883818a51ac98fec156f4f1_3.jpg',
            '/webflow-assets/images/688381764d4b4f0b1c7987e0_6.jpg',
            '/webflow-assets/images/688380b679056a0be53f6d41_Frame-26.jpg',
            '/webflow-assets/images/68c883fec4b4d3727926bd2c_DSC_0300-copy.jpg',
            '/webflow-assets/images/6883805ca03ba6c0a0ba7581_Frame-27.jpg',
            '/webflow-assets/images/6883803cfdf2b295d6678634_Frame-24.jpg',
            '/webflow-assets/images/68837d89eba765eb136ab983_Frame-39.jpg',
            '/webflow-assets/images/68837db4472b7f5b8e77e05a_Frame-31.jpg',
            '/webflow-assets/images/68c8850d03f555204c2ee456_IMG_3697-copy.jpg',
            '/webflow-assets/images/68837d6bc361aa0ba1045eac_Frame-29.jpg',
            '/webflow-assets/images/68837f8b87816ec2e8483c95_Frame-34.jpg',
            '/webflow-assets/images/68837d5617f63550b5539b02_Frame-17.jpg',
            '/webflow-assets/images/68837d44674b0564cb25ee4c_Frame-16.jpg',
            '/webflow-assets/images/68837d2c60170917e2be19d0_Frame-15.jpg',
            '/webflow-assets/images/68837d17539b7261a6d30b69_Frame-14.jpg',
            '/webflow-assets/images/68837d02448fcace0169fb09_Frame-12.jpg',
            '/webflow-assets/images/68837cedbacf503578cc291a_Frame-11.jpg',
            '/webflow-assets/images/68825b61f81ce44f12673973_Screenshot-2025-07-24-at-19.12.13.png',
            '/webflow-assets/images/688236fed9cc28b78fb10d04_IMG_2963-1.avif',
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

        return view('glossary', compact('navItems', 'sections'));
    }

    public function faq()
    {
        return view('faq');
    }

    public function testimonials()
    {
        return view('testimonials');
    }

    public function financing()
    {
        return view('financing');
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
                'expires_label' => $promotions->formatGlobal('long') ?: $promotions->couponExpiresLabel($coupon, 'long'),
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
            'landing_page' => trim((string) $request->input('landing_page')),
            'referrer' => trim((string) $request->input('referrer')),
            'geo_location' => trim((string) $request->input('geo_location')),
            'utm_source' => trim((string) $request->input('utm_source')),
            'utm_medium' => trim((string) $request->input('utm_medium')),
            'utm_campaign' => trim((string) $request->input('utm_campaign')),
            'utm_content' => trim((string) $request->input('utm_content')),
            'utm_term' => trim((string) $request->input('utm_term')),
            'matchtype' => trim((string) $request->input('matchtype')),
            'device' => trim((string) $request->input('device')),
            'creative' => trim((string) $request->input('creative')),
            'gclid' => trim((string) $request->input('gclid')),
            'fbclid' => trim((string) $request->input('fbclid')),
            'msclkid' => trim((string) $request->input('msclkid')),
        ];

        $validated = validator($payload, [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'city' => 'nullable|string|max:100',
            'message' => 'nullable|string|max:3000',
            'page_url' => 'nullable|string|max:1000',
            'landing_page' => 'nullable|string|max:1000',
            'referrer' => 'nullable|string|max:1000',
            'geo_location' => 'nullable|string|max:255',
            'utm_source' => 'nullable|string|max:255',
            'utm_medium' => 'nullable|string|max:255',
            'utm_campaign' => 'nullable|string|max:255',
            'utm_content' => 'nullable|string|max:255',
            'utm_term' => 'nullable|string|max:255',
            'matchtype' => 'nullable|string|max:255',
            'device' => 'nullable|string|max:255',
            'creative' => 'nullable|string|max:255',
            'gclid' => 'nullable|string|max:255',
            'fbclid' => 'nullable|string|max:255',
            'msclkid' => 'nullable|string|max:255',
        ])->validate();

        Lead::query()->create([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'city' => $validated['city'],
            'message' => $validated['message'],
            'page_url' => $validated['page_url'],
            'utm_source' => $validated['utm_source'],
            'utm_medium' => $validated['utm_medium'],
            'utm_campaign' => $validated['utm_campaign'],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'meta' => [
                'request_id' => (string) $request->headers->get('x-request-id', ''),
                'via' => 'classic-site-contact-form',
                'geo_location' => $validated['geo_location'],
                'landing_page' => $validated['landing_page'],
                'referrer' => $validated['referrer'],
                'utm_content' => $validated['utm_content'],
                'utm_term' => $validated['utm_term'],
                'matchtype' => $validated['matchtype'],
                'device' => $validated['device'],
                'creative' => $validated['creative'],
                'gclid' => $validated['gclid'],
                'fbclid' => $validated['fbclid'],
                'msclkid' => $validated['msclkid'],
            ],
        ]);

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

        try {
            Mail::raw(implode("\n", $bodyLines), function ($message) use ($subject, $validated): void {
                $message->to('sofalieti@gmail.com')
                    ->replyTo($validated['email'], $validated['full_name'])
                    ->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::warning('Lead notification email failed', [
                'email' => $validated['email'],
                'error' => $e->getMessage(),
            ]);
        }

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
                        'landing_page' => $validated['landing_page'],
                        'referrer' => $validated['referrer'],
                        'ip_address' => $request->ip(),
                        'geo_location' => $validated['geo_location'],
                        'utm_source' => $validated['utm_source'],
                        'utm_medium' => $validated['utm_medium'],
                        'utm_campaign' => $validated['utm_campaign'],
                        'utm_content' => $validated['utm_content'],
                        'utm_term' => $validated['utm_term'],
                        'matchtype' => $validated['matchtype'],
                        'device' => $validated['device'],
                        'creative' => $validated['creative'],
                        'gclid' => $validated['gclid'],
                        'fbclid' => $validated['fbclid'],
                        'msclkid' => $validated['msclkid'],
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
            ->take(6)
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
            'image' => $this->extractWindowsPropertyListingFeaturedImage($window) ?? '',
        ];
    }

    private function extractWindowsPropertyListingFeaturedImage(WindowsWebflowItem $window): ?string
    {
        $fieldData = is_array($window->field_data) ? $window->field_data : [];

        $fromFieldData = $this->extractImageUrl($fieldData, ['property-listing---featured-image']);
        if ($fromFieldData !== null && $fromFieldData !== '') {
            return $fromFieldData;
        }

        $stored = $window->wf_property_listing_featured_image;
        if (is_array($stored) && ! empty($stored['url'])) {
            return webflow_image_url((string) $stored['url']);
        }

        return null;
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
            ->sortBy(fn ($item) => (int) data_get($item->field_data, 'order', 999))
            ->values()
            ->map(fn ($item) => $this->mapWindowsIndexCard($item))
            ->filter(fn ($card) => ($card['slug'] ?? '') !== '')
            ->values();

        return view('windows.index', compact('windows'));
    }

    public function doorsIndex()
    {
        $doors = DoorsWebflowItem::query()
            ->where('is_archived', false)
            ->where('is_draft', false)
            ->get()
            ->filter(function ($item) {
                $fd = is_array($item->field_data) ? $item->field_data : [];

                if (($fd['hide'] ?? false) === true) {
                    return false;
                }

                return ($fd['slug'] ?? '') !== '';
            })
            ->sortBy(fn ($item) => (int) data_get($item->field_data, 'order', 999))
            ->values()
            ->map(function ($item) {
                $fd = is_array($item->field_data) ? $item->field_data : [];
                $slug = (string) ($fd['slug'] ?? '');

                if ($slug === '') {
                    return null;
                }

                return [
                    'name' => (string) ($fd['name'] ?? ''),
                    'slug' => $slug,
                    'image' => $this->extractImageUrl($fd, [
                        'blog-post---thumbnail-image-v1',
                        'blog-post---featured-image',
                        'property-listing---thumbnail-image-v1',
                        'property-listing---featured-image',
                    ]) ?? '',
                ];
            })
            ->filter(fn ($card) => ($card['slug'] ?? '') !== '')
            ->values();

        return view('doors.index', compact('doors'));
    }

    public function brandIndex()
    {
        $brands = BrandsWebflowItem::query()
            ->where('is_archived', false)
            ->where('is_draft', false)
            ->get()
            ->sortBy(fn ($brand) => (int) data_get($brand->field_data, 'order', 999))
            ->values()
            ->map(fn ($brand) => $this->mapBrandIndexCard($brand))
            ->filter(fn ($card) => ($card['slug'] ?? '') !== '')
            ->values();

        return view('brand.index', compact('brands'));
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
        // Local brand hero image takes priority over Webflow featured image
        $localBrandHeroPath = "webflow-assets/images/brand-hero/{$slug}.avif";
        $localBrandHeroAbsolute = public_path($localBrandHeroPath);
        if (file_exists($localBrandHeroAbsolute)) {
            $v = @filemtime($localBrandHeroAbsolute) ?: 1;
            $featuredImage = '/'.$localBrandHeroPath.'?v='.$v;
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

        $windowsTitle   = $fieldData['windows-titles']         ?? "Explore {$name}'s Window Types";
        $doorsTitle     = $fieldData['doors-title']            ?? "Explore {$name}'s Door Types";
        $sidebarMaterialGroups = $this->buildBrandSidebarMaterialGroups($brand, $fieldData);
        $controls = app(PromotionControlService::class);
        $brandPricing = $this->resolveBrandPromotionPricing(
            $brand,
            (string) ($fieldData['slug'] ?? $slug),
            (string) ($brand->webflow_item_id ?? ''),
            $controls
        );
        $brandHeroFormHtml = $brandPricing
            ? $controls->pricingHtmlFromMap($brandPricing, 'per window installed')
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
            'brandHeroFormHtml' => $brandHeroFormHtml,
            'brandPromotionPricing' => $brandPricing,
        ]);
    }

    public function doorBrandBySlug(string $slug)
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
        $featuredImage = $this->extractImageUrl($fieldData, ['featured-image']);
        if ($featuredImage === null && is_array($brand->wf_featured_image ?? null)) {
            $featuredImage = $brand->wf_featured_image['url'] ?? null;
        }
        // Local brand hero image takes priority over Webflow featured image
        $localBrandHeroPath = "webflow-assets/images/brand-hero/{$slug}.avif";
        $localBrandHeroAbsolute = public_path($localBrandHeroPath);
        if (file_exists($localBrandHeroAbsolute)) {
            $v = @filemtime($localBrandHeroAbsolute) ?: 1;
            $featuredImage = '/'.$localBrandHeroPath.'?v='.$v;
        }
        $name        = $fieldData['name'] ?? 'Brand';

        // Window types referenced by this brand (kept on door-brand pages)
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

        // Door-specific descriptions synced from database/data/door-brands.json.
        // Public FAQ content is loaded only from page-metadata files.
        $doorBrand = DoorBrand::query()->where('slug', $slug)->first();

        $description = $doorBrand && trim((string) $doorBrand->description) !== ''
            ? $doorBrand->description
            : $this->defaultDoorBrandDescription($name);

        $windowsTitle   = $fieldData['windows-titles']  ?? "Explore {$name}'s Window Types";
        $doorsTitle     = ($doorBrand && trim((string) $doorBrand->doors_title) !== '')
            ? $doorBrand->doors_title
            : ($fieldData['doors-title'] ?? "Explore {$name}'s Door Types");

        // Door-brand hero price = cheapest priced door (Promotions → Door Types)
        // among all Doors linked to this brand via the `doors-brands` reference.
        $controls = app(PromotionControlService::class);
        $brandPricing = $this->resolveCheapestDoorPricingForBrand($brand, $controls);
        $brandHeroFormHtml = $brandPricing
            ? $controls->pricingHtmlFromMap($brandPricing, 'per door installed')
            : null;

        return view('door-brands.show', [
            'brandFieldData'  => $fieldData,
            'name'            => $name,
            'slug'            => $fieldData['slug'] ?? $slug,
            'logo'            => $logo,
            'featuredImage'   => $featuredImage,
            'description'     => $description,
            'windowTypes'     => $windowTypes,
            'doorTypes'       => $doorTypes,
            'windowsTitle'    => $windowsTitle,
            'doorsTitle'      => $doorsTitle,
            'brandHeroFormHtml' => $brandHeroFormHtml,
            'brandPromotionPricing' => $brandPricing,
        ]);
    }

    /**
     * Cheapest priced door linked to a brand.
     *
     * Scans the Doors collection for items whose `doors-brands` reference contains
     * this brand, looks up each door's Promotions price (Door Types tab) and returns
     * the one with the lowest final price. Returns null when no linked door is priced.
     *
     * @return array{base: string, final: string}|null
     */
    private function resolveCheapestDoorPricingForBrand(BrandsWebflowItem $brand, PromotionControlService $controls): ?array
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
            ->each(function (DoorsWebflowItem $door) use ($brandId, $controls, &$best, &$bestValue) {
                $fd = is_array($door->field_data) ? $door->field_data : [];

                $brandRefs = $fd['doors-brands'] ?? [];
                if (! is_array($brandRefs) || ! in_array($brandId, $brandRefs, true)) {
                    return;
                }

                $pricing = $controls->doorPricing(
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

    private function priceToFloat(string $value): ?float
    {
        $clean = preg_replace('/[^0-9.]/', '', $value);

        return ($clean === '' || $clean === null) ? null : (float) $clean;
    }

    private function defaultDoorBrandDescription(string $name): string
    {
        $safe = e($name);

        return '<p>Upgrade your home with '.$safe.' doors, professionally installed by Deluxe Windows. '
            .'From patio and sliding doors that open your living space to the outdoors, to durable, '
            .'energy-efficient entry doors, '.$safe.' combines lasting quality with modern performance.</p>'
            .'<p>Our Bay Area team helps you choose the right door line for your style and budget, then '
            .'handles precise measurement, professional installation, and a final inspection so everything '
            .'operates perfectly.</p>'
            .'<ul role="list">'
            .'<li>Energy-efficient glass and weather-tight seals</li>'
            .'<li>Durable, low-maintenance materials and finishes</li>'
            .'<li>Custom sizes, hardware, and configurations</li>'
            .'<li>Authorized Bay Area installation</li>'
            .'</ul>';
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
        $pagePromotionAvailable = str_contains($heroFormHtml, 'promo-offer-card');

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
            'pagePromotionAvailable' => $pagePromotionAvailable,
        ]);
    }

    public function doorTypeBySlug(string $slug)
    {
        $slug = strtolower(trim($slug));

        $doorType = DoorTypesWebflowItem::query()
            ->where('field_data->slug', $slug)
            ->orWhere('webflow_item_id', $slug)
            ->orderByDesc('id')
            ->first();

        abort_if(! $doorType, 404);

        $fieldData = is_array($doorType->field_data ?? null) ? $doorType->field_data : [];

        $name  = $fieldData['name'] ?? 'Door Type';
        $about = $fieldData['property-listing---about'] ?? '';
        $featuredImage = $this->extractImageUrl($fieldData, [
            'property-listing---featured-image',
            'property-listing---thumbnail-image-v1',
        ]);

        $parentBrand = $doorType->webflowReference('property-listing---agent');
        abort_if(! $parentBrand, 404);

        $brandFd = is_array($parentBrand->field_data) ? $parentBrand->field_data : [];
        $brandName = $brandFd['name'] ?? '';
        $brandSlug = $brandFd['slug'] ?? '';
        $brandLogo = $this->extractImageUrl($brandFd, ['logo-svg', 'brand-logo', 'agent---avatar-photo', 'agent-avatar-photo']);

        // Sidebar reuses the brand collections layout, same as window-type pages.
        $windowTypes = $parentBrand->webflowReferences('window-types')
            ->map(function ($wt) {
                $fd = is_array($wt->field_data) ? $wt->field_data : [];
                $wtSlug = $fd['slug'] ?? '';

                return $wtSlug !== ''
                    ? [
                        'name' => $fd['name'] ?? '',
                        'slug' => $wtSlug,
                        'image' => $this->extractImageUrl($fd, [
                            'property-listing---thumbnail-image-v1',
                            'property-listing---featured-image',
                        ]) ?? '',
                    ]
                    : null;
            })
            ->filter()
            ->values();

        $sidebarMaterialGroups = $this->buildBrandSidebarMaterialGroups($parentBrand, $brandFd);

        // Sibling door types of the same brand (for the "explore" grid).
        $doorTypes = $parentBrand->webflowReferences('doors-type-marvin')
            ->map(function ($dt) use ($slug) {
                $fd = is_array($dt->field_data) ? $dt->field_data : [];
                $dtSlug = $fd['slug'] ?? '';

                return $dtSlug !== '' && $dtSlug !== $slug
                    ? [
                        'name' => $fd['name'] ?? '',
                        'slug' => $dtSlug,
                        'image' => $this->extractImageUrl($fd, [
                            'property-listing---thumbnail-image-v1',
                            'property-listing---featured-image',
                        ]) ?? '',
                    ]
                    : null;
            })
            ->filter()
            ->values();

        // Referenced series link to /brand-collections/{slug}; skip refs whose
        // slug has no published brand-collections page.
        $publishedCollectionSlugs = BrandCollectionsWebflowItem::query()
            ->where('is_archived', false)
            ->where('is_draft', false)
            ->pluck('field_data->slug')
            ->filter()
            ->flip();

        $collections = $doorType->webflowReferences('door-type-collections-2')
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
            ->filter(fn ($c) => $c['name'] !== '' && $c['slug'] !== '' && isset($publishedCollectionSlugs[$c['slug']]))
            ->values();

        $collectionsTitle = $fieldData['explore-collection-text'] ?? "Explore {$brandName} Collections";

        $controls = app(PromotionControlService::class);
        $doorTypePricing = $this->resolveDoorTypePricing($slug, $parentBrand, $controls);
        $heroFormHtml = $doorTypePricing
            ? $controls->pricingHtmlFromMap($doorTypePricing, 'per door installed')
            : $controls->priceHtml('2165', '$1299', 'per door installed');
        $pagePromotionAvailable = str_contains($heroFormHtml, 'promo-offer-card');

        return view('door-types.show', [
            'name'                  => $name,
            'slug'                  => $fieldData['slug'] ?? $slug,
            'aboutHtml'             => $about,
            'featuredImage'         => $featuredImage,
            'brandName'             => $brandName,
            'brandSlug'             => $brandSlug,
            'logo'                  => $brandLogo,
            'windowTypes'           => $windowTypes,
            'doorTypes'             => $doorTypes,
            'sidebarMaterialGroups' => $sidebarMaterialGroups,
            'collections'           => $collections,
            'collectionsTitle'      => $collectionsTitle,
            'heroFormHtml'          => $heroFormHtml,
            'pagePromotionAvailable' => $pagePromotionAvailable,
        ]);
    }

    /**
     * Door-type hero price priority:
     * 1) Promotions price of the matching door material (slug contains the material)
     * 2) cheapest priced door linked to the parent brand
     *
     * @return array{base: string, final: string}|null
     */
    private function resolveDoorTypePricing(string $doorTypeSlug, BrandsWebflowItem $brand, PromotionControlService $controls): ?array
    {
        $normalized = str_replace('aluminium', 'aluminum', $doorTypeSlug);
        foreach (['wood-clad', 'fiberglass', 'aluminum', 'vinyl', 'steel', 'wood'] as $material) {
            if (! str_contains($normalized, $material)) {
                continue;
            }
            $materialSlug = "{$material}-doors";
            $door = DoorsWebflowItem::query()
                ->where('field_data->slug', $materialSlug)
                ->first();
            $pricing = $controls->doorPricing(
                (string) ($door->webflow_item_id ?? ''),
                $materialSlug
            );
            if ($pricing !== null) {
                return $pricing;
            }
            break;
        }

        return $this->resolveCheapestDoorPricingForBrand($brand, $controls);
    }

    public function legacyBrandCollectionRedirect(string $slug)
    {
        $slug = strtolower(trim($slug));

        $exists = BrandCollectionsWebflowItem::query()
            ->where('is_archived', false)
            ->where('is_draft', false)
            ->where('field_data->slug', $slug)
            ->exists();

        abort_unless($exists, 404);

        return redirect('/brand-collections/'.$slug, 301);
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
        $heroImage = $this->extractImageUrl($fieldData, ['main-project-image', 'client-logo']);
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
        $heroImage = $this->extractImageUrl($fieldData, ['hero-image']) ?? '';
        $localCountyHeroPath = "webflow-assets/images/county-hero/{$slug}.avif";
        $localCountyHeroAbsolute = public_path($localCountyHeroPath);
        if (file_exists($localCountyHeroAbsolute)) {
            $version = @filemtime($localCountyHeroAbsolute) ?: 1;
            $heroImage = '/'.$localCountyHeroPath.'?v='.$version;
        }
        $countyIntro = $fieldData['county-intro'] ?? '';
        $cities = $this->resolveCountyHubCities($fieldData);
        $featuredBrands = $this->resolveCountyHubFeaturedBrands($fieldData);

        return view('county-hub-pages.show', compact(
            'slug',
            'countyName',
            'heroImage',
            'countyIntro',
            'cities',
            'featuredBrands',
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
        $heroImage = $this->extractImageUrl($fieldData, ['hero-image']) ?? '';
        $localServiceAreaHeroPath = "webflow-assets/images/service-area-hero/{$slug}.avif";
        $localServiceAreaHeroAbsolute = public_path($localServiceAreaHeroPath);
        if (file_exists($localServiceAreaHeroAbsolute)) {
            $version = @filemtime($localServiceAreaHeroAbsolute) ?: 1;
            $heroImage = '/'.$localServiceAreaHeroPath.'?v='.$version;
        }
        $paragraph1 = $fieldData['city-paragraph-1'] ?? '';
        $paragraph2 = $fieldData['city-paragraph-2'] ?? '';
        $windowTypes = $this->loadServiceAreaWindowTypes();
        $featuredBrands = $this->resolveServiceAreaFeaturedBrands($fieldData);
        $countyHubSlug = $this->resolveCountyHubSlug($fieldData['county-page'] ?? null);

        return view('window-replacement.show', compact(
            'slug',
            'cityName',
            'cityLabel',
            'countyName',
            'heroImage',
            'paragraph1',
            'paragraph2',
            'windowTypes',
            'featuredBrands',
            'countyHubSlug',
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
    private function resolveCountyHubFeaturedBrands(array $fieldData): \Illuminate\Support\Collection
    {
        $cityIds = array_values(array_filter(
            is_array($fieldData['cities-in-county'] ?? null) ? $fieldData['cities-in-county'] : [],
            fn ($id) => is_string($id) && $id !== ''
        ));
        if ($cityIds === []) {
            return collect();
        }

        $brandIds = [];

        try {
            $items = WindowReplacementWebflowItem::query()
                ->whereIn('webflow_item_id', $cityIds)
                ->where('is_archived', false)
                ->where('is_draft', false)
                ->get();

            foreach ($items as $item) {
                $ids = data_get($item->field_data, 'featured-brands', []);
                if (is_array($ids)) {
                    $brandIds = array_merge($brandIds, $ids);
                }
            }
        } catch (\Throwable) {
            $brandIds = [];
        }

        if ($brandIds === []) {
            foreach ($this->loadWindowReplacementImportItems() as $importItem) {
                if (($importItem['isDraft'] ?? false) || ($importItem['isArchived'] ?? false)) {
                    continue;
                }
                if (! in_array((string) ($importItem['id'] ?? ''), $cityIds, true)) {
                    continue;
                }

                $ids = data_get($importItem, 'fieldData.featured-brands', []);
                if (is_array($ids)) {
                    $brandIds = array_merge($brandIds, $ids);
                }
            }
        }

        $brandIds = array_values(array_unique(array_filter(
            $brandIds,
            fn ($id) => is_string($id) && $id !== ''
        )));

        return $this->resolveServiceAreaFeaturedBrands(['featured-brands' => $brandIds]);
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
                return webflow_image_url($value['url']);
            }
            if (is_string($value) && $value !== '') {
                return webflow_image_url($value);
            }
        }

        return null;
    }

    private function templateWindowTypeHeroImageUrl(string $slug): ?string
    {
        return $this->templateMaterialHeroImageUrl('window-type-hero', $slug);
    }

    private function templateDoorTypeHeroImageUrl(string $slug): ?string
    {
        return $this->templateMaterialHeroImageUrl('door-type-hero', $slug);
    }

    private function templateMaterialHeroImageUrl(string $directory, string $slug): ?string
    {
        $slug = strtolower(trim($slug));
        if ($slug === '') {
            return null;
        }

        $base = 'webflow-assets/images/'.$directory.'/'.$slug;
        foreach (['avif', 'png', 'webp', 'jpg', 'jpeg'] as $extension) {
            $relativePath = $base.'.'.$extension;
            $absolutePath = public_path($relativePath);
            if (File::exists($absolutePath)) {
                $v = @filemtime($absolutePath) ?: 1;
                return '/'.$relativePath.'?v='.$v;
            }
        }

        $legacyPath = 'webflow-assets/images/'.$directory.'/'.$slug.'-hero-bg-v1.avif';
        $legacyAbsolute = public_path($legacyPath);
        if (File::exists($legacyAbsolute)) {
            $v = @filemtime($legacyAbsolute) ?: 1;
            return '/'.$legacyPath.'?v='.$v;
        }

        return null;
    }

    /**
     * Sidebar material groups for brand pages (matches deluxewindows.com/brands/* layout).
     *
     * @return \Illuminate\Support\Collection<int, array{name: string, sublabel: ?string, collections: \Illuminate\Support\Collection, visible: bool, insertWindowTypesAfter: bool}>
     */
    /**
     * Sidebar groups for brand / brand-collection / window-type / door-type pages:
     * 1) materials linked to the brand (Webflow order)
     * 2) brand-collections linked via parent-brand
     * 3) for each material: heading + collections that use that material
     */
    private function buildBrandSidebarMaterialGroups(BrandsWebflowItem $brand, array $fieldData): \Illuminate\Support\Collection
    {
        $brandMaterials = $brand->webflowReferences('materials')
            ->map(function ($material) {
                $fd = is_array($material->field_data) ? $material->field_data : [];
                $name = trim((string) ($fd['name'] ?? ''));
                $id = (string) ($material->webflow_item_id ?? '');

                return ($name !== '' && $id !== '')
                    ? ['id' => $id, 'name' => $name]
                    : null;
            })
            ->filter()
            ->values();

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
        $assignedSlugs = [];

        foreach ($brandMaterials as $material) {
            $materialName = $material['name'];
            $materialId = $material['id'];
            $matched = $brandCollections
                ->filter(fn ($c) => $this->brandCollectionMatchesMaterial($c, $materialName, $materialId))
                ->values();

            if ($matched->isEmpty()) {
                continue;
            }

            foreach ($matched as $collection) {
                $assignedSlugs[$collection['slug']] = true;
            }

            if ($materialName === 'Vinyl Windows' && $showVinylSubcategories) {
                foreach (['/ New Construction', '/ Replacement'] as $sublabel) {
                    $subMatched = $matched
                        ->filter(fn ($c) => $this->brandCollectionMatchesVinylSubcategory($c, $sublabel))
                        ->values();

                    if ($subMatched->isEmpty()) {
                        continue;
                    }

                    $groups->push([
                        'name'                   => $materialName,
                        'sublabel'               => $sublabel,
                        'collections'            => $subMatched,
                        'visible'                => true,
                        'insertWindowTypesAfter' => $insertWindowTypesAfterFirst,
                    ]);
                    $insertWindowTypesAfterFirst = false;
                }

                continue;
            }

            $groups->push([
                'name'                   => $materialName,
                'sublabel'               => null,
                'collections'            => $matched,
                'visible'                => true,
                'insertWindowTypesAfter' => $insertWindowTypesAfterFirst,
            ]);
            $insertWindowTypesAfterFirst = false;
        }

        // Collections tied to the brand but not matched to any brand material.
        $leftover = $brandCollections
            ->reject(fn ($c) => isset($assignedSlugs[$c['slug']]))
            ->values();

        if ($leftover->isNotEmpty()) {
            $groups->push([
                'name'                   => 'Other collections',
                'sublabel'               => null,
                'collections'            => $leftover,
                'visible'                => true,
                'insertWindowTypesAfter' => $insertWindowTypesAfterFirst,
            ]);
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
        $controls = app(PromotionControlService::class);
        $seriesSlug = (string) ($fieldData['slug'] ?? '');
        $seriesPricing = $controls->seriesPricing(
            (string) ($collection->webflow_item_id ?? ''),
            $seriesSlug
        );
        if ($seriesPricing !== null) {
            return $controls->pricingHtmlFromMap($seriesPricing, 'per window installed');
        }

        // Default for collection pages: inherit from Mainmaterial (Windows item).
        $mainMaterialRef = (string) ($fieldData['mainmaterial'] ?? '');
        if ($mainMaterialRef !== '') {
            $mainMaterial = WindowsWebflowItem::query()
                ->where('webflow_item_id', $mainMaterialRef)
                ->orWhere('field_data->slug', $mainMaterialRef)
                ->first();
            if ($mainMaterial instanceof WindowsWebflowItem) {
                $mainFd = is_array($mainMaterial->field_data) ? $mainMaterial->field_data : [];
                $mainSlug = trim((string) ($mainFd['slug'] ?? ''));
                if ($mainSlug !== '') {
                    $inherited = $controls->windowTypePricing(
                        (string) ($mainMaterial->webflow_item_id ?? ''),
                        $mainSlug
                    );
                    if ($inherited !== null) {
                        return $controls->pricingHtmlFromMap($inherited, 'per window installed');
                    }
                }
            }
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
        $windowTypePricing = app(PromotionControlService::class)->windowTypePricing(
            (string) ($windowType->webflow_item_id ?? ''),
            $windowTypeSlug
        );
        if ($windowTypePricing !== null) {
            return app(PromotionControlService::class)->pricingHtmlFromMap($windowTypePricing, 'per window installed');
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
        string $brandWebflowId,
        PromotionControlService $controls,
    ): ?array {
        $override = $controls->brandPricing($brandWebflowId, $brandSlug);
        if ($override !== null) {
            return $override;
        }

        // Default brand pricing source: linked Windows main type.
        $mainType = $brand->webflowReference('windowmaintype');
        if ($mainType instanceof WindowsWebflowItem) {
            $mainTypeFd = is_array($mainType->field_data) ? $mainType->field_data : [];
            $mainTypeSlug = trim((string) ($mainTypeFd['slug'] ?? ''));
            if ($mainTypeSlug !== '') {
                $inherited = $controls->windowTypePricing(
                    (string) ($mainType->webflow_item_id ?? ''),
                    $mainTypeSlug
                );
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
                    $inherited = $controls->windowTypePricing(
                        (string) ($mainTypeRow->webflow_item_id ?? ''),
                        $mainTypeSlug
                    );
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
            $inherited = $controls->windowTypePricing(
                (string) ($material->webflow_item_id ?? ''),
                $materialSlug
            );
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

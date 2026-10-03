<?php

declare(strict_types=1);

namespace App\Support;

use App\Services\PromotionControlService;
use App\Services\Seo\OrganizationSchema;
use App\Services\YelpReviewsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Shared content model for the home-page design concepts (/home-concept/{variant}).
 * Every concept renders the same blocks and menu items from this array; only the design differs.
 */
final class HomeConceptContent
{
    public const VARIANTS = ['editorial', 'noir', 'swiss', 'nordic', 'poster'];

    /**
     * @param  Collection<int, array{name: string, slug: string, image: string, summary: string}>  $homeWindows
     * @return array<string, mixed>
     */
    public static function build(Collection $homeWindows, string $variant): array
    {
        $promo = app(PromotionControlService::class);
        $yelp = app(YelpReviewsService::class)->payload();

        $materialMeta = [
            'vinyl-windows' => ['from' => 499, 'was' => 832, 'tag' => 'Best value', 'for' => 'Everyday upgrades, rentals, tight budgets', 'note' => 'Lifetime warranty'],
            'wood-clad-windows' => ['from' => 549, 'was' => 915, 'tag' => 'Most chosen', 'for' => 'Warm interiors, zero exterior upkeep', 'note' => '20-year glass warranty'],
            'fiberglass-windows' => ['from' => 549, 'was' => 915, 'tag' => 'Coastal pick', 'for' => 'Fog, salt air, large openings', 'note' => '8× stronger than vinyl'],
            'aluminum-windows' => ['from' => 549, 'was' => 915, 'tag' => 'Modern', 'for' => 'Slim sightlines, mid-century homes', 'note' => 'Thermally broken frames'],
            'aluminum-clad-windows' => ['from' => 549, 'was' => 915, 'tag' => 'Premium', 'for' => 'Wood inside, metal armor outside', 'note' => 'Custom exterior colors'],
            'wood-windows' => ['from' => 589, 'was' => 982, 'tag' => 'Heritage', 'for' => 'Victorians, Craftsman, historic districts', 'note' => 'Paint- or stain-grade'],
            'steel-windows' => ['from' => 589, 'was' => 982, 'tag' => 'Architect', 'for' => 'Design-forward, ultra-thin frames', 'note' => 'Narrowest profiles'],
        ];
        $bySlug = $homeWindows->keyBy('slug');
        $materials = collect(array_keys($materialMeta))->map(function (string $slug) use ($materialMeta, $bySlug) {
            $w = $bySlug->get($slug, []);

            return [
                'slug' => $slug,
                'name' => (string) ($w['name'] ?? Str::of($slug)->replace('-', ' ')->title()),
                'short' => (string) Str::of($slug)->replace('-windows', '')->replace('-', ' ')->title(),
                'image' => (string) ($w['image'] ?? ''),
                'summary' => (string) ($w['summary'] ?? ''),
            ] + $materialMeta[$slug];
        })->values();

        $brands = [
            ['slug' => 'marvin', 'image' => '/webflow-assets/images/6915aaca08003de3e1e57018_marvin-logo-black.svg', 'name' => 'Marvin'],
            ['slug' => 'andersen', 'image' => '/webflow-assets/images/6915aaaa3027924fb18fb47c_andersen_logo_tm_rectangle_rgb.svg', 'name' => 'Andersen'],
            ['slug' => 'milgard', 'image' => '/webflow-assets/images/6915aaea85f921adbca8a4e7_milgard.svg', 'name' => 'Milgard'],
            ['slug' => 'anlin', 'image' => '/webflow-assets/images/6915c80af96503367881f15f_anlin2.svg', 'name' => 'Anlin'],
            ['slug' => 'jeld-wen', 'image' => '/webflow-assets/images/6915aa60264a3c99f69524c6_jv.svg', 'name' => 'Jeld-Wen'],
            ['slug' => 'simonton', 'image' => '/webflow-assets/images/6915aa3a24afaaa0a93dd455_Simonton_PrimaryLogo_Inline_RGB_Gradient_0822-1-2048x427.avif', 'name' => 'Simonton'],
            ['slug' => 'ply-gem', 'image' => '/webflow-assets/images/6915aa80238022f9197f6973_pl.svg', 'name' => 'Ply Gem'],
            ['slug' => 'alside', 'image' => '/webflow-assets/images/6915b29da8bcdcb16ec593b6_alside-logo.svg', 'name' => 'Alside'],
            ['slug' => 'western-window-systems', 'image' => '/webflow-assets/images/6915b390bad100b6e6176ea7_westerngroup.svg', 'name' => 'Western Window Systems'],
            ['slug' => 'all-weather-architectural-aluminum', 'image' => '/webflow-assets/images/6915bedcc5e0152198130ace_footer-logo__1__2-removebg-preview.avif', 'name' => 'All Weather'],
            ['slug' => 'italwindows', 'image' => '/webflow-assets/images/6915bd3fcaf3c1f1ff04d9dd_italwindows.svg', 'name' => 'Italwindows'],
        ];

        $doors = [
            ['slug' => 'vinyl-doors', 'image' => '/webflow-assets/images/6862d4e603255742b1319d0f_Frame%2048.avif', 'name' => 'Vinyl', 'text' => 'Sliding & French patio doors. Lowest upkeep, lowest price.'],
            ['slug' => 'wood-clad-doors', 'image' => '/webflow-assets/images/687e2bbbcf84c63258838bc4_homeguide-marvin-signature-windows-and-doors.avif', 'name' => 'Wood Clad', 'text' => 'Warm wood inside, weather armor outside. Multi-slide up to 50 ft.'],
            ['slug' => 'fiberglass-doors', 'image' => '/webflow-assets/images/684e94a86602a96b9775c003_Frame%2048.avif', 'name' => 'Fiberglass', 'text' => 'Entry doors that read as wood and never warp or rot.'],
            ['slug' => 'aluminum-doors', 'image' => '', 'name' => 'Aluminum', 'text' => 'Slim-frame bifold & pivot doors for modern façades.'],
            ['slug' => 'steel-doors', 'image' => '', 'name' => 'Steel', 'text' => 'Hairline sightlines, maximum glass. The architect’s door.'],
            ['slug' => 'wood-doors', 'image' => '', 'name' => 'Wood', 'text' => 'True solid wood for Craftsman, Tudor and Victorian homes.'],
        ];

        $counties = [
            'San Mateo' => 'san-mateo-county', 'San Francisco' => 'san-francisco-county', 'Santa Clara' => 'santa-clara-county',
            'Alameda' => 'alameda-county', 'Contra Costa' => 'contra-costa-county', 'Marin' => 'marin-county',
            'Sonoma' => 'sonoma-county', 'Napa' => 'napa-county', 'Solano' => 'solano-county',
        ];

        $nav = [
            'Windows' => [
                'href' => '/windows',
                'cols' => [
                    'Materials' => ['All' => '/windows'] + $materials->mapWithKeys(fn ($m) => [$m['short'] => '/windows/'.$m['slug']])->all(),
                    'Brands' => ['All' => '/brands'] + collect($brands)->mapWithKeys(fn ($b) => [$b['name'] => '/brands/'.$b['slug']])->all(),
                ],
            ],
            'Doors' => [
                'href' => '/doors',
                'cols' => [
                    'Materials' => ['All' => '/doors'] + collect($doors)->mapWithKeys(fn ($d) => [$d['name'] => '/doors/'.$d['slug']])->all(),
                    'Door Brands' => ['All' => '/brands'] + collect($brands)->mapWithKeys(fn ($b) => [$b['name'] => '/door-brands/'.$b['slug']])->all(),
                ],
            ],
            'Learning Center' => [
                'href' => '/blog',
                'cols' => [
                    'Guides' => [
                        'Knowledge Articles' => '/blog',
                        'Window Measurement Guide' => '/blog/how-to-measure-windows-for-replacement',
                        'Tips for Windows Replacement' => '/blog/comprehensive-guide-to-choosing-the-right-replacement-windows',
                        'Window Buyer’s Guide' => '/blog/window-buyers-guide',
                        'Door Buyer’s Guide' => '/blog/the-ultimate-door-buyers-guide',
                        'Glossary' => '/glossary',
                        'Frequently Asked Questions' => '/faq',
                    ],
                ],
            ],
            'Resources & Support' => [
                'href' => '/contacts',
                'cols' => [
                    'Resources' => [
                        'Special Offers' => '/special-offers', 'Financing' => '/financing', 'Gallery' => '/gallery',
                        'About Us' => '/about', 'Contact Us' => '/contacts', 'Testimonials' => '/testimonials',
                    ],
                    'Premium Service Areas' => collect($counties)->mapWithKeys(fn ($slug, $name) => [$name.' County' => '/county-hub-pages/'.$slug])->all(),
                ],
            ],
        ];

        $reviews = collect($yelp['reviews'] ?? [])
            ->filter(fn ($r) => (int) ($r['rating'] ?? 0) >= 4 && mb_strlen((string) ($r['text'] ?? '')) > 80)
            ->take(6)
            ->values();

        return [
            'variant' => $variant,
            'variants' => self::VARIANTS,
            'discountPercent' => $promo->globalDiscountPercent(),
            'discountLabel' => $promo->globalDiscountLabel(),
            'promoEnd' => $promo->endDate(),
            'phoneDisplay' => site_phone_display(),
            'phoneTel' => site_phone_tel(),
            'yelpRating' => (string) ($yelp['business']['rating_label'] ?? '4.5'),
            'yelpCount' => (string) ($yelp['business']['reviews_label'] ?? '257'),
            'yelpUrl' => (string) ($yelp['business']['yelp_url'] ?? 'https://www.yelp.com/biz/deluxe-windows-burlingame-3'),
            'reviews' => $reviews,
            'materials' => $materials,
            'brands' => $brands,
            'doors' => $doors,
            'tiers' => [
                ['name' => 'Vinyl', 'from' => 499, 'was' => 832, 'brands' => 'Anlin · Milgard · Simonton · Alside'],
                ['name' => 'Wood Clad · Fiberglass · Aluminum', 'from' => 549, 'was' => 915, 'brands' => 'Andersen · Marvin · Milgard Ultra · Western'],
                ['name' => 'Wood · Steel', 'from' => 589, 'was' => 982, 'brands' => 'Marvin · Jeld-Wen · Italwindows'],
            ],
            'included' => [
                'Removal & disposal of old units',
                'Low-E, argon-filled dual-pane glass',
                'AAMA-certified installation crew',
                'Interior & exterior trim, caulk, seal',
                'Haul-away and job-site clean-up',
                'Warranty registration & paperwork',
            ],
            'steps' => [
                ['Consultation', 'A product specialist comes to you (or you visit the Burlingame showroom), measures every opening and shows real corner samples. 45–60 min. No commission pressure.'],
                ['Written quote', 'Itemized by opening: brand, series, glass package, hardware, labor. Delivered within 24 hours. Locked for 30 days.'],
                ['Factory order', 'Built to the ⅛″. Typical lead time 3–6 weeks. We pull permits where the city requires them.'],
                ['Install day', 'Most homes finish in 1–2 days. Old units hauled away, openings sealed and trimmed, every sash operated with you before we leave.'],
            ],
            'stats' => [
                ['Energy bills', '−30%', 'up to, Low-E dual-pane vs. single-pane*'],
                ['Resale value', '~70%', 'of project cost recouped at sale*'],
                ['Street noise', '−50%', 'with laminated glass option'],
            ],
            'statsNote' => '*Industry averages (ENERGY STAR®, Remodeling Cost vs. Value). Results vary by home and glass package.',
            'guarantee' => [
                ['Lifetime', 'Vinyl windows', 'Full lifetime transferable warranty on parts and labor. It stays with the house when you sell.'],
                ['20 yrs', 'Glass — clad, fiberglass, aluminum', 'Seal failure, fogging and Low-E coating defects.'],
                ['10 yrs', 'Everything else', 'Hardware, balances, weatherstrip and screens — on top of the manufacturer’s own coverage.'],
            ],
            'guaranteeNote' => 'Manufacturer’s warranty on glass and frame — lifetime on qualifying products. Full terms travel with your written quote.',
            'certs' => [
                ['image' => '/webflow-assets/images/6998617839debbabce241e8e_6915aaca08003de3e1e57018_marvin-logo-black.svg', 'name' => 'Marvin'],
                ['image' => '/webflow-assets/images/69200ccf66431025ccaabea5_milgard.svg', 'name' => 'Milgard'],
                ['image' => '/webflow-assets/images/69986178667bcb1013476512_6915aaaa3027924fb18fb47c_andersen_logo_tm_rectangle_rgb.svg', 'name' => 'Andersen'],
            ],
            'counties' => $counties,
            'showroom' => OrganizationSchema::showroomAddressLine(),
            'hours' => 'Mon–Fri 8–6 · Sat 9–3',
            'license' => 'CA Lic. #'.OrganizationSchema::CSLB_LICENSE,
            'pros' => [
                ['Architects', 'Spec support, shop drawings, brand-agnostic recommendations — from design development through punch list.'],
                ['Contractors', 'Measure, order, deliver, install on your schedule. Capacity to run several jobs at once, with long-term warranties on every one.'],
                ['Property managers', 'Occupied-unit replacements with clear scopes, tenant-friendly scheduling and a single invoice.'],
            ],
            'faqs' => [
                ['How much do replacement windows really cost?', 'With the current promotion, vinyl starts at $499 per window installed, wood-clad / fiberglass / aluminum at $549 and wood or steel at $589. Size, glass package and how many openings you do at once move the number — the quote is free and itemized.'],
                ['Do I have to replace every window at once?', 'No. Many homeowners phase the work room by room. Doing more openings in one visit lowers the per-window price, so we show you both numbers.'],
                ['How long does installation take?', 'A typical 8–12 window home is finished in one to two days. We work room by room, so nothing is left open overnight.'],
                ['Is financing available?', 'Yes — monthly plans including promotional 0% options for qualified buyers. Your consultant will show current terms.'],
                ['What warranty do I get?', 'Vinyl: full lifetime transferable warranty on parts and labor. Other materials: 20 years on glass, 10 years on all other parts, plus the manufacturer’s coverage.'],
            ],
            'nav' => $nav,
            'footerLinks' => [
                'About Us' => '/about', 'Windows' => '/windows', 'Doors' => '/doors', 'Brands' => '/brands',
                'Glossary' => '/glossary', 'Contacts Us' => '/contacts', 'Testimonials' => '/testimonials',
                'Financing' => '/financing', 'FAQs' => '/faq', 'Privacy Policy' => '/privacy-policy', 'Terms of Use' => '/terms',
            ],
            'images' => [
                'hero' => '/webflow-assets/images/home-concept/home-concept-hero.jpg',
                'before' => '/webflow-assets/images/home-concept/home-concept-before.jpg',
                'after' => '/webflow-assets/images/home-concept/home-concept-after.jpg',
                'installer' => '/webflow-assets/images/home-concept/home-concept-installer.jpg',
                'consult' => '/webflow-assets/images/home-concept/home-concept-consult.jpg',
                'samples' => '/webflow-assets/images/home-concept/home-concept-samples.jpg',
            ],
        ];
    }
}

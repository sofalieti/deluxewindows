<?php

declare(strict_types=1);

namespace App\Support;

use App\Services\Seo\PageMetadata;

/**
 * Microsoft Ads audience paths. The homepage block and these pages
 * are shown only to Bing / Microsoft visitors.
 */
final class AudiencePaths
{
    /**
     * @return list<array{
     *     slug: string,
     *     image: string,
     *     image_alt: string,
     *     title: string,
     *     card: string,
     *     h1: string,
     *     lede: string,
     *     points: list<array{title: string, text: string}>,
     *     steps: list<string>,
     *     form_id: string
     * }>
     */
    public static function all(): array
    {
        return [
            [
                'slug' => 'homeowners',
                'image' => '/webflow-assets/images/audience/audience-homeowners.jpg',
                'image_alt' => 'California craftsman home with new white windows',
                'title' => 'Homeowners',
                'card' => 'Beautiful, efficient windows and doors for the home you love.',
                'h1' => 'Windows and doors for the home you love',
                'lede' => 'A measured replacement for the house you live in: the right product, a clear price, and one crew from the first visit to the last cleanup.',
                'points' => [
                    ['title' => 'A written estimate', 'text' => 'We measure at the house and price the windows and doors you actually need, not a package from a brochure.'],
                    ['title' => 'The right product', 'text' => 'Vinyl, fiberglass, wood-clad, and aluminum from the brands we install across the Bay Area.'],
                    ['title' => 'One crew', 'text' => 'The same company orders, delivers, and installs, then stands behind the work.'],
                ],
                'steps' => [
                    'Tell us which rooms and what is failing: drafts, fogged glass, a door that sticks.',
                    'We measure, recommend a line, and leave a written price.',
                    'Installation is scheduled around the house, with the opening closed the same day.',
                ],
                'form_id' => 'Who: Homeowners',
            ],
            [
                'slug' => 'hoas',
                'image' => '/webflow-assets/images/audience/audience-hoas.jpg',
                'image_alt' => 'Matching townhomes with the same windows across the community',
                'title' => 'HOAs',
                'card' => 'Consistent, coordinated window replacement for your whole community.',
                'h1' => 'One standard for the whole community',
                'lede' => 'Boards need matching windows, a schedule the association can approve, and a crew that treats occupied homes as someone’s house.',
                'points' => [
                    ['title' => 'One specification', 'text' => 'Profiles, colors, and grids stay consistent from the first building to the last.'],
                    ['title' => 'A board-ready plan', 'text' => 'Scope, phasing, and price in a form the board can vote on.'],
                    ['title' => 'Resident notices', 'text' => 'A site lead who tells people when we are coming and who to call if something is off.'],
                ],
                'steps' => [
                    'We walk a sample of homes and lock the product standard with the board.',
                    'The work is phased so the community is never a construction site all at once.',
                    'Each home is closed up the day we open it, with a punch list before we leave the phase.',
                ],
                'form_id' => 'Who: HOAs',
            ],
            [
                'slug' => 'property-managers',
                'image' => '/webflow-assets/images/audience/audience-property-managers.jpg',
                'image_alt' => 'Apartment building with uniform replacement windows',
                'title' => 'Property Managers',
                'card' => 'Dependable service with clear scheduling and resident communication.',
                'h1' => 'Replacement that fits an occupied building',
                'lede' => 'Units stay livable. You get one contact for access, delays, and the punch list, and owners get a record of what was replaced.',
                'points' => [
                    ['title' => 'Phased around occupancy', 'text' => 'We work unit by unit, with access windows you can put on the calendar.'],
                    ['title' => 'One point of contact', 'text' => 'Scheduling, resident questions, and delays go through the same person.'],
                    ['title' => 'A paper trail', 'text' => 'Counts, locations, and product lines documented for the owner and the next inspection.'],
                ],
                'steps' => [
                    'Send the building, the unit count, and what has to be replaced this cycle.',
                    'We confirm access, measure, and schedule so residents know the day.',
                    'Completed units are listed back to you before we invoice the phase.',
                ],
                'form_id' => 'Who: Property Managers',
            ],
            [
                'slug' => 'designers-architects',
                'image' => '/webflow-assets/images/audience/audience-designers.jpg',
                'image_alt' => 'Modern home with large black-framed windows',
                'title' => 'Designers & Architects',
                'card' => 'Product options and specs to match your design intent.',
                'h1' => 'Specs that match the design',
                'lede' => 'Sightlines, finishes, and divided lites that follow the elevation, with product data before the set is locked.',
                'points' => [
                    ['title' => 'Product data early', 'text' => 'Shop drawings and options while the design can still change, not after bid day.'],
                    ['title' => 'The specified system', 'text' => 'We price the window and door you drew, and say so when a detail will not build.'],
                    ['title' => 'Finishes that match', 'text' => 'Exterior color, interior clad, hardware, and grids chosen against the rest of the room.'],
                ],
                'steps' => [
                    'Share the elevation and the performance you need: light, egress, or a view.',
                    'We return product options with sizes that fit the rough opening.',
                    'The specified line is what gets ordered, unless you change it.',
                ],
                'form_id' => 'Who: Designers and Architects',
            ],
            [
                'slug' => 'builders',
                'image' => '/webflow-assets/images/audience/audience-builders.jpg',
                'image_alt' => 'New house with windows set during construction',
                'title' => 'Builders',
                'card' => 'A trade partner for new construction and materials-only orders.',
                'h1' => 'A trade partner for the opening package',
                'lede' => 'New-construction windows and doors, installed or supplied. Delivery timed to the opening, not to a guess from the first sketch.',
                'points' => [
                    ['title' => 'Supply or install', 'text' => 'We can furnish the package only, or measure, deliver, and set it.'],
                    ['title' => 'Timed to the opening', 'text' => 'Field measure when the rough opening is ready, then a delivery date you can build around.'],
                    ['title' => 'A dealer, not a catalog', 'text' => 'One order for windows and exterior doors, with someone who answers when the site changes.'],
                ],
                'steps' => [
                    'Send the schedule and whether you want material only or a set crew.',
                    'We measure the openings that are actually framed.',
                    'Product arrives labeled for the opening, or our crew sets it.',
                ],
                'form_id' => 'Who: Builders',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(string $slug): ?array
    {
        foreach (self::all() as $audience) {
            if ($audience['slug'] === $slug) {
                return $audience;
            }
        }

        return null;
    }

    public static function url(string $slug): string
    {
        return '/for/'.$slug;
    }

    /**
     * @param  array<string, mixed>  $audience
     */
    public static function metadata(array $audience): PageMetadata
    {
        $base = rtrim((string) config('services.sitemap.base_url', 'https://www.deluxewindows.com'), '/');
        $path = self::url((string) $audience['slug']);
        $title = $audience['title'].' | Deluxe Windows';
        $description = (string) $audience['card'];

        return new PageMetadata(
            key: 'audience-'.$audience['slug'],
            path: $path,
            title: $title,
            description: $description,
            canonical: $base.$path,
            ogTitle: $title,
            ogDescription: $description,
            ogImage: null,
            ogType: 'website',
            robots: 'noindex,nofollow',
            twitterTitle: $title,
            twitterDescription: $description,
            twitterImage: null,
            twitterCard: 'summary_large_image',
            h1: (string) $audience['h1'],
            h1Subline: '',
            faq: [],
            schema: ['primary_type' => 'WebPage'],
        );
    }
}

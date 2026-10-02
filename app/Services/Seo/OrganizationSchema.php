<?php

declare(strict_types=1);

namespace App\Services\Seo;

/**
 * Site-wide company schema (not page-specific).
 * Emitted once per page via partials/schema-organization.blade.php.
 *
 * HomeAndConstructionBusiness is a LocalBusiness subtype — this is the
 * local-business structured data Google reads for NAP / hours / address.
 */
final class OrganizationSchema
{
    public const SHOWROOM_STREET = '1676 Gilbreth Rd';

    public const SHOWROOM_CITY = 'Burlingame';

    public const SHOWROOM_REGION = 'CA';

    public const SHOWROOM_POSTAL = '94010';

    public const SHOWROOM_COUNTRY = 'US';

    public const CSLB_LICENSE = '695262';

    public static function id(): string
    {
        return self::baseUrl().'/#organization';
    }

    public static function showroomAddressLine(): string
    {
        return self::SHOWROOM_STREET.', '.self::SHOWROOM_CITY.', '.self::SHOWROOM_REGION.' '.self::SHOWROOM_POSTAL;
    }

    /**
     * @return array<string, mixed>
     */
    public static function toArray(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'HomeAndConstructionBusiness',
            '@id' => self::id(),
            'name' => 'Deluxe Windows',
            'legalName' => 'Deluxe Windows, Inc.',
            'url' => self::baseUrl(),
            'telephone' => site_phone_tel(),
            'image' => self::baseUrl().'/webflow-assets/images/686ad2b4b668ce59a9c25b0e_White.avif',
            'logo' => self::baseUrl().'/webflow-assets/images/686ad2b4b668ce59a9c25b0e_White.avif',
            'description' => 'Premium window and door replacement for San Francisco Bay Area homes. 30+ years, 100% employee owned.',
            'priceRange' => '$$',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => self::SHOWROOM_STREET,
                'addressLocality' => self::SHOWROOM_CITY,
                'addressRegion' => self::SHOWROOM_REGION,
                'postalCode' => self::SHOWROOM_POSTAL,
                'addressCountry' => self::SHOWROOM_COUNTRY,
            ],
            'identifier' => [
                '@type' => 'PropertyValue',
                'name' => 'CSLB License',
                'value' => self::CSLB_LICENSE,
            ],
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => '4.9',
                'reviewCount' => '231',
                'bestRating' => '5',
            ],
            'openingHoursSpecification' => [
                [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                    'opens' => '08:00',
                    'closes' => '18:00',
                ],
                [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => 'Saturday',
                    'opens' => '09:00',
                    'closes' => '15:00',
                ],
            ],
            'areaServed' => [
                '@type' => 'GeoCircle',
                'geoMidpoint' => [
                    '@type' => 'GeoCoordinates',
                    'latitude' => 37.5630,
                    'longitude' => -122.0329,
                ],
                'geoRadius' => '100000',
            ],
        ];
    }

    private static function baseUrl(): string
    {
        return rtrim((string) config(
            'services.sitemap.base_url',
            'https://deluxewindows.com'
        ), '/');
    }
}

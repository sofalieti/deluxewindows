<?php

declare(strict_types=1);

namespace App\Services\Seo;

/**
 * Site-wide company schema (not page-specific).
 * Emitted once per page via partials/schema-organization.blade.php.
 */
final class OrganizationSchema
{
    public static function id(): string
    {
        return self::baseUrl().'/#organization';
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
            'url' => self::baseUrl(),
            'telephone' => site_phone_tel(),
            'description' => 'Premium window and door replacement for San Francisco Bay Area homes. 30+ years, 100% employee owned.',
            'priceRange' => '$$',
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
            'https://www.deluxewindows.com'
        ), '/');
    }
}

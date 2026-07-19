<?php

declare(strict_types=1);

namespace App\Services\Seo;

class SchemaBuilder
{
    /**
     * @return list<array<string, mixed>>
     */
    public function build(PageMetadata $metadata): array
    {
        $organizationId = OrganizationSchema::id();
        $schemas = [
            $this->webPage($metadata, $organizationId),
            $this->breadcrumbs($metadata),
        ];

        $primary = trim((string) ($metadata->schema['primary_type'] ?? 'WebPage'));
        $primarySchema = $this->primarySchema($primary, $metadata, $organizationId);
        if ($primarySchema !== null) {
            $schemas[] = $primarySchema;
        }

        if ($metadata->faq !== []) {
            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                '@id' => $metadata->canonical.'#faq',
                'mainEntity' => array_map(
                    static fn (array $item): array => [
                        '@type' => 'Question',
                        'name' => $item['question'],
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => strip_tags($item['answer']),
                        ],
                    ],
                    $metadata->faq
                ),
            ];
        }

        foreach ((array) ($metadata->schema['extra'] ?? []) as $extra) {
            if (is_array($extra)) {
                $schemas[] = ['@context' => 'https://schema.org', ...$extra];
            }
        }

        foreach ((array) ($metadata->schema['replace'] ?? []) as $replacement) {
            if (! is_array($replacement)) {
                continue;
            }
            $replacementTypes = (array) ($replacement['@type'] ?? []);
            $schemas = array_values(array_filter(
                $schemas,
                static fn (array $schema): bool => array_intersect(
                    (array) ($schema['@type'] ?? []),
                    $replacementTypes
                ) === []
            ));
            $schemas[] = $replacement;
        }

        return $schemas;
    }

    /**
     * @return array<string, mixed>
     */
    private function webPage(PageMetadata $metadata, string $organizationId): array
    {
        $type = match ((string) ($metadata->schema['primary_type'] ?? '')) {
            'AboutPage', 'ContactPage', 'CollectionPage' => $metadata->schema['primary_type'],
            default => 'WebPage',
        };

        return [
            '@context' => 'https://schema.org',
            '@type' => $type,
            '@id' => $metadata->canonical.'#webpage',
            'url' => $metadata->canonical,
            'name' => $metadata->title,
            'description' => $metadata->description,
            'isPartOf' => ['@id' => $this->baseUrl().'/#website'],
            'publisher' => ['@id' => $organizationId],
            ...(is_string($metadata->ogImage) ? [
                'primaryImageOfPage' => [
                    '@type' => 'ImageObject',
                    'url' => $metadata->ogImage,
                ],
            ] : []),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function breadcrumbs(PageMetadata $metadata): array
    {
        $items = [[
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Home',
            'item' => $this->baseUrl(),
        ]];

        $segments = array_values(array_filter(explode('/', trim($metadata->path, '/'))));
        $path = '';
        foreach ($segments as $index => $segment) {
            $path .= '/'.$segment;
            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 2,
                'name' => $path === $metadata->path
                    ? $metadata->title
                    : ucwords(str_replace('-', ' ', $segment)),
                'item' => rtrim($this->baseUrl(), '/').$path,
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            '@id' => $metadata->canonical.'#breadcrumbs',
            'itemListElement' => $items,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function primarySchema(
        string $type,
        PageMetadata $metadata,
        string $organizationId
    ): ?array {
        $data = is_array($metadata->schema['data'] ?? null)
            ? $metadata->schema['data']
            : [];

        return match ($type) {
            'WebSite' => [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                '@id' => $this->baseUrl().'/#website',
                'url' => $this->baseUrl(),
                'name' => 'Deluxe Windows',
                'publisher' => ['@id' => $organizationId],
            ],
            'Product' => $this->productSchema($metadata, $data),
            'Service' => [
                '@context' => 'https://schema.org',
                '@type' => 'Service',
                '@id' => $metadata->canonical.'#service',
                'name' => (string) ($data['name'] ?? $metadata->title),
                'description' => $metadata->description,
                'url' => $metadata->canonical,
                'provider' => ['@id' => $organizationId],
                ...(! empty($data['area_served']) ? ['areaServed' => $data['area_served']] : []),
            ],
            'BlogPosting' => [
                '@context' => 'https://schema.org',
                '@type' => 'BlogPosting',
                '@id' => $metadata->canonical.'#article',
                'headline' => (string) ($data['headline'] ?? $metadata->title),
                'description' => $metadata->description,
                'url' => $metadata->canonical,
                'publisher' => ['@id' => $organizationId],
                ...(is_string($metadata->ogImage) ? ['image' => $metadata->ogImage] : []),
                ...(! empty($data['date_published']) ? ['datePublished' => $data['date_published']] : []),
                ...(! empty($data['date_modified']) ? ['dateModified' => $data['date_modified']] : []),
            ],
            'DefinedTermSet' => [
                '@context' => 'https://schema.org',
                '@type' => 'DefinedTermSet',
                '@id' => $metadata->canonical.'#terms',
                'name' => $metadata->title,
                'description' => $metadata->description,
                'url' => $metadata->canonical,
            ],
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function productSchema(PageMetadata $metadata, array $data): array
    {
        $product = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            '@id' => $metadata->canonical.'#product',
            'name' => (string) ($data['name'] ?? $metadata->title),
            'description' => $metadata->description,
            'url' => $metadata->canonical,
            ...(is_string($metadata->ogImage) ? ['image' => $metadata->ogImage] : []),
            'brand' => [
                '@type' => 'Brand',
                'name' => (string) ($data['brand'] ?? 'Deluxe Windows'),
            ],
        ];

        $offer = $this->brandOfferForPath($metadata->path, $metadata->canonical);
        if ($offer !== []) {
            $product['offers'] = $offer;
        }

        return $product;
    }

    /**
     * @return array<string, mixed>
     */
    private function brandOfferForPath(string $path, string $canonical): array
    {
        try {
            /** @var \App\Services\BrandPromotionPricing $resolver */
            $resolver = app(\App\Services\BrandPromotionPricing::class);
            $pricing = $resolver->forPath($path);
            if ($pricing === null) {
                return [];
            }

            $unit = str_starts_with($path, '/door-brands/')
                ? 'per door installed'
                : 'per window installed';

            return $resolver->toSchemaOffer($pricing, $canonical, $unit);
        } catch (\Throwable) {
            return [];
        }
    }

    private function baseUrl(): string
    {
        return rtrim((string) config(
            'services.sitemap.base_url',
            'https://www.deluxewindows.com'
        ), '/');
    }
}

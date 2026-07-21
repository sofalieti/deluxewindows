<?php

declare(strict_types=1);

namespace App\Services\Seo;

final readonly class PageMetadata
{
    /**
     * @param list<array{question: string, answer: string}> $faq
     * @param array<string, mixed> $schema
     */
    public function __construct(
        public string $key,
        public string $path,
        public string $title,
        public string $description,
        public string $canonical,
        public string $ogTitle,
        public string $ogDescription,
        public ?string $ogImage,
        public string $ogType,
        public string $robots,
        public string $twitterTitle,
        public string $twitterDescription,
        public ?string $twitterImage,
        public string $twitterCard,
        public string $h1,
        public string $h1Subline,
        public array $faq,
        public array $schema,
    ) {
    }

    public static function fallback(string $path, string $baseUrl): self
    {
        $title = 'Deluxe Windows | Window Replacement – San Francisco Bay Area';
        $description = 'Energy-efficient window and door replacement for Bay Area homes.';
        $normalizedPath = $path === '/' ? '/' : '/'.trim($path, '/');

        return new self(
            key: 'fallback',
            path: $normalizedPath,
            title: $title,
            description: $description,
            canonical: rtrim($baseUrl, '/').($normalizedPath === '/' ? '' : $normalizedPath),
            ogTitle: $title,
            ogDescription: $description,
            ogImage: null,
            ogType: 'website',
            robots: 'index,follow',
            twitterTitle: $title,
            twitterDescription: $description,
            twitterImage: null,
            twitterCard: 'summary_large_image',
            h1: $title,
            h1Subline: '',
            faq: [],
            schema: ['primary_type' => 'WebPage'],
        );
    }

    /** Full visible H1 text (main + optional subline). */
    public function h1Full(): string
    {
        if ($this->h1Subline === '') {
            return $this->h1;
        }

        return trim($this->h1.' '.$this->h1Subline);
    }
}

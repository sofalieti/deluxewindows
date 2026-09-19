<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class YelpReviewsService
{
    private const DATA_RELATIVE = 'data/yelp-reviews/reviews.json';

    /** @var array{mtime: int, payload: array<string, mixed>}|null */
    private ?array $cache = null;

    /**
     * @return array{
     *     business: array<string, mixed>,
     *     reviews: list<array<string, mixed>>
     * }|null
     */
    public function payload(int $minRating = 3): ?array
    {
        $loaded = $this->load();
        if ($loaded === null) {
            return null;
        }

        $reviews = array_values(array_filter(
            $loaded['reviews'],
            static function (array $review) use ($minRating): bool {
                return (int) $review['rating'] >= $minRating
                    && trim((string) $review['text']) !== '';
            }
        ));

        if ($reviews === []) {
            return null;
        }

        return [
            'business' => $loaded['business'],
            'reviews' => $reviews,
        ];
    }

    /**
     * @return array{business: array<string, mixed>, reviews: list<array<string, mixed>>}|null
     */
    private function load(): ?array
    {
        $path = database_path(self::DATA_RELATIVE);
        if (! is_file($path)) {
            Log::warning('Yelp reviews dataset is missing.', ['path' => $path]);

            return null;
        }

        $mtime = (int) filemtime($path);
        if ($this->cache !== null && $this->cache['mtime'] === $mtime) {
            return $this->cache['payload'];
        }

        try {
            $decoded = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            Log::error('Yelp reviews dataset is invalid JSON.', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if (! is_array($decoded) || ! isset($decoded['business'], $decoded['reviews']) || ! is_array($decoded['reviews'])) {
            Log::error('Yelp reviews dataset is missing required keys.', ['path' => $path]);

            return null;
        }

        $business = $this->normalizeBusiness($decoded['business']);
        $reviews = [];
        foreach ($decoded['reviews'] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $review = $this->normalizeReview($row);
            if ($review !== null) {
                $reviews[] = $review;
            }
        }

        usort($reviews, static fn (array $a, array $b): int => $b['published_at_unix'] <=> $a['published_at_unix']);

        $payload = [
            'business' => $business,
            'reviews' => $reviews,
        ];
        $this->cache = ['mtime' => $mtime, 'payload' => $payload];

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function normalizeBusiness(array $raw): array
    {
        $rating = round((float) ($raw['rating'] ?? 4.5), 1);
        $count = (int) ($raw['reviews_number'] ?? $raw['recommended_reviews_count'] ?? 0);

        return [
            'name' => (string) ($raw['name'] ?? 'Deluxe Windows'),
            'yelp_url' => (string) ($raw['yelp_url'] ?? 'https://www.yelp.com/biz/deluxe-windows-burlingame-3'),
            'rating' => $rating,
            'reviews_number' => $count,
            'rating_label' => number_format($rating, 1),
            'reviews_label' => number_format($count),
        ];
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>|null
     */
    private function normalizeReview(array $raw): ?array
    {
        $id = trim((string) ($raw['id'] ?? ''));
        $text = trim((string) ($raw['text'] ?? ''));
        $rating = (int) ($raw['rating'] ?? 0);
        if ($id === '' || $text === '' || $rating < 1 || $rating > 5) {
            return null;
        }

        $publishedAt = (string) ($raw['published_at'] ?? '');
        $unix = (int) ($raw['published_at_unix'] ?? 0);
        $date = null;
        if ($publishedAt !== '') {
            try {
                $date = Carbon::parse($publishedAt);
            } catch (\Throwable) {
                $date = null;
            }
        }
        if ($date === null && $unix > 0) {
            $date = Carbon::createFromTimestamp($unix);
        }

        $photoRel = trim((string) ($raw['photo'] ?? ''));
        $photoUrl = $this->publicPhotoUrl($photoRel);
        if ($photoUrl === null) {
            $original = trim((string) ($raw['photo_original_url'] ?? ''));
            $photoUrl = $original !== '' ? $original : null;
        }

        $reviewPhotos = [];
        foreach ($raw['review_photos'] ?? [] as $photo) {
            if (! is_array($photo)) {
                continue;
            }
            $file = trim((string) ($photo['file'] ?? ''));
            $url = $this->publicPhotoUrl($file) ?? (trim((string) ($photo['original_url'] ?? '')) ?: null);
            if ($url !== null) {
                $reviewPhotos[] = $url;
            }
        }

        $author = trim((string) ($raw['author'] ?? 'Yelp user'));

        return [
            'id' => $id,
            'author' => $author,
            'initials' => $this->initials($author),
            'rating' => $rating,
            'text' => $text,
            'yelp_review_url' => (string) ($raw['yelp_review_url'] ?? ''),
            'photo_url' => $photoUrl,
            'review_photos' => $reviewPhotos,
            'published_at_unix' => $date?->timestamp ?? $unix,
            'published_label' => $date?->diffForHumans(['short' => true]) ?? '',
            'published_iso' => $date?->toIso8601String() ?? $publishedAt,
        ];
    }

    private function publicPhotoUrl(string $relative): ?string
    {
        $relative = str_replace('\\', '/', ltrim($relative, '/'));
        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }

        $publicPath = public_path('yelp-reviews/'.$relative);
        if (! is_file($publicPath)) {
            return null;
        }

        return '/yelp-reviews/'.$relative;
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $letters = '';
        foreach ($parts as $part) {
            $part = trim($part, " \t\n\r\0\x0B.");
            if ($part === '') {
                continue;
            }
            $letters .= mb_strtoupper(mb_substr($part, 0, 1));
            if (mb_strlen($letters) >= 2) {
                break;
            }
        }

        return $letters !== '' ? $letters : 'Y';
    }
}

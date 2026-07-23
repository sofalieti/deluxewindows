<?php

declare(strict_types=1);

namespace App\Services;

final class LeadSpamGuard
{
    /**
     * @param  array{
     *     full_name?: string,
     *     email?: string,
     *     phone?: string,
     *     city?: string,
     *     message?: string
     * }  $fields
     * @return array{spam: bool, reason: string|null}
     */
    public function inspect(array $fields): array
    {
        $haystack = $this->haystack($fields);

        if ($haystack === '') {
            return ['spam' => false, 'reason' => null];
        }

        if (config('lead_spam.block_cyrillic', true) && $this->containsCyrillic($haystack)) {
            return ['spam' => true, 'reason' => 'cyrillic'];
        }

        $matched = $this->matchedStopword($haystack);
        if ($matched !== null) {
            return ['spam' => true, 'reason' => 'stopword:'.$matched];
        }

        return ['spam' => false, 'reason' => null];
    }

    public function isSpam(array $fields): bool
    {
        return $this->inspect($fields)['spam'];
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function haystack(array $fields): string
    {
        $parts = [];
        foreach (['full_name', 'email', 'phone', 'city', 'message'] as $key) {
            $value = trim((string) ($fields[$key] ?? ''));
            if ($value !== '') {
                $parts[] = $value;
            }
        }

        return mb_strtolower(implode("\n", $parts));
    }

    private function containsCyrillic(string $text): bool
    {
        return (bool) preg_match('/\p{Cyrillic}/u', $text);
    }

    private function matchedStopword(string $haystack): ?string
    {
        $words = (array) config('lead_spam.stopwords', []);

        foreach ($words as $word) {
            if (! is_string($word)) {
                continue;
            }
            $needle = mb_strtolower(trim($word));
            if ($needle === '') {
                continue;
            }

            if ($this->containsStopword($haystack, $needle)) {
                return $needle;
            }
        }

        return null;
    }

    private function containsStopword(string $haystack, string $needle): bool
    {
        // Multi-word / phrase: plain substring (already lowercased).
        if (str_contains($needle, ' ') || str_contains($needle, '-') || str_contains($needle, '.')) {
            return str_contains($haystack, $needle);
        }

        // Single token: word-boundary style so "slot" does not match unrelated words
        // when we only list distinctive tokens; still allow unicode letters.
        $pattern = '/(?<![\p{L}\p{N}_])'.preg_quote($needle, '/').'(?![\p{L}\p{N}_])/u';

        return (bool) preg_match($pattern, $haystack);
    }
}

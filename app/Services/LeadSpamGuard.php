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

        if (config('lead_spam.block_gibberish_names', true)) {
            foreach (['full_name', 'city'] as $field) {
                $value = trim((string) ($fields[$field] ?? ''));
                if ($value !== '' && $this->looksLikeGibberishToken($value)) {
                    return ['spam' => true, 'reason' => 'gibberish:'.$field];
                }
            }

            $email = trim((string) ($fields['email'] ?? ''));
            $local = strstr($email, '@', true);
            if (is_string($local) && $local !== '' && $this->looksLikeGibberishToken($local)) {
                return ['spam' => true, 'reason' => 'gibberish:email'];
            }
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

    /**
     * Detect bot junk like "kpPkLqFYbTtRsOzMZRHWmfm": long letter blobs,
     * mixed case, very few vowels, or long consonant runs.
     */
    private function looksLikeGibberishToken(string $value): bool
    {
        $tokens = preg_split('/[\s\-_\.]+/u', $value) ?: [];

        foreach ($tokens as $token) {
            $letters = preg_replace('/[^A-Za-z]/', '', $token) ?? '';
            $len = strlen($letters);
            if ($len < 12) {
                continue;
            }

            $vowelCount = preg_match_all('/[aeiouy]/i', $letters);
            $vowelRatio = $vowelCount / $len;
            $hasLower = (bool) preg_match('/[a-z]/', $letters);
            $hasUpper = (bool) preg_match('/[A-Z]/', $letters);
            $mixedCase = $hasLower && $hasUpper;
            $internalCapSwitch = (bool) preg_match('/[a-z][A-Z]|[A-Z]{2,}[a-z]/', $letters);
            $longConsonantRun = (bool) preg_match('/[bcdfghjklmnpqrstvwxz]{5,}/i', $letters);

            // Classic random bot name: long + mixed case + almost no vowels.
            if ($len >= 14 && $mixedCase && $vowelRatio < 0.28) {
                return true;
            }

            // Single long blob with sparse vowels (even if all lower/upper).
            if ($len >= 16 && $vowelRatio < 0.22) {
                return true;
            }

            // CamelCase noise with consonant piles (kpPkLqFYbTt…).
            if ($len >= 12 && $internalCapSwitch && $longConsonantRun && $vowelRatio < 0.35) {
                return true;
            }
        }

        // Whole value is one long letter-only string with no real word spacing.
        $compact = preg_replace('/[^A-Za-z]/', '', $value) ?? '';
        $compactLen = strlen($compact);
        if ($compactLen >= 18 && ! preg_match('/\s/u', trim($value))) {
            $vowelCount = preg_match_all('/[aeiouy]/i', $compact);
            if (($vowelCount / $compactLen) < 0.28) {
                return true;
            }
        }

        return false;
    }
}

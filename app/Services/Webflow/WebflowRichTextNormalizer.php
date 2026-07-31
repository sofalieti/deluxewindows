<?php

declare(strict_types=1);

namespace App\Services\Webflow;

final class WebflowRichTextNormalizer
{
    /**
     * CMS rich text is rendered below the page's canonical H1.
     * Remove embedded H1 blocks because the page title already exists.
     */
    public static function removeEmbeddedH1(mixed $value): mixed
    {
        if (is_string($value)) {
            return preg_replace('/<h1\b[^>]*>.*?<\/h1\s*>/is', '', $value) ?? $value;
        }

        if (! is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            $value[$key] = self::removeEmbeddedH1($item);
        }

        return $value;
    }
}

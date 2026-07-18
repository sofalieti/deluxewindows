<?php

declare(strict_types=1);

namespace App\Services\Seo;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PageMetadataRepository
{
    /** @var array<string, array{mtime: int, metadata: PageMetadata}> */
    private array $cache = [];

    public function current(): PageMetadata
    {
        $path = '/'.ltrim(request()->path(), '/');
        if ($path === '//') {
            $path = '/';
        }

        return $this->forPath($path);
    }

    public function forPath(string $path): PageMetadata
    {
        $path = $this->normalizePath($path);
        $baseUrl = $this->baseUrl();
        $file = $this->fileForPath($path);

        try {
            if (! File::exists($file)) {
                throw new InvalidArgumentException("Page metadata file is missing: {$file}");
            }

            $mtime = File::lastModified($file);
            if (isset($this->cache[$file]) && $this->cache[$file]['mtime'] === $mtime) {
                return $this->cache[$file]['metadata'];
            }

            $decoded = json_decode((string) File::get($file), true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($decoded)) {
                throw new InvalidArgumentException("Page metadata must be a JSON object: {$file}");
            }

            $metadata = $this->hydrate($decoded, $path, $baseUrl);
            $this->cache[$file] = ['mtime' => $mtime, 'metadata' => $metadata];

            return $metadata;
        } catch (\Throwable $e) {
            Log::warning('Page metadata could not be loaded', [
                'path' => $path,
                'file' => $file,
                'error' => $e->getMessage(),
            ]);

            return PageMetadata::fallback($path, $baseUrl);
        }
    }

    /**
     * @return array{valid: int, invalid: list<string>}
     */
    public function validateAll(): array
    {
        $root = $this->root();
        $valid = 0;
        $invalid = [];
        $keys = [];
        $paths = [];

        if (! File::isDirectory($root)) {
            return ['valid' => 0, 'invalid' => ["Page metadata directory is missing: {$root}"]];
        }

        foreach (File::allFiles($root) as $file) {
            if ($file->getExtension() !== 'json') {
                continue;
            }

            try {
                $decoded = json_decode((string) File::get($file->getPathname()), true, 512, JSON_THROW_ON_ERROR);
                if (! is_array($decoded)) {
                    throw new InvalidArgumentException('Root value must be an object.');
                }
                $path = $this->normalizePath((string) ($decoded['path'] ?? ''));
                $metadata = $this->hydrate($decoded, $path, $this->baseUrl());

                $expected = realpath($this->fileForPath($metadata->path));
                if ($expected === false || $expected !== realpath($file->getPathname())) {
                    throw new InvalidArgumentException('File location does not match its public path.');
                }
                if (isset($keys[$metadata->key])) {
                    throw new InvalidArgumentException("Duplicate metadata key: {$metadata->key}");
                }
                if (isset($paths[$metadata->path])) {
                    throw new InvalidArgumentException("Duplicate public path: {$metadata->path}");
                }
                $keys[$metadata->key] = true;
                $paths[$metadata->path] = true;
                $valid++;
            } catch (\Throwable $e) {
                $invalid[] = $file->getPathname().': '.$e->getMessage();
            }
        }

        return ['valid' => $valid, 'invalid' => $invalid];
    }

    public function clearCache(): void
    {
        $this->cache = [];
    }

    public function root(): string
    {
        return database_path('data/page-metadata');
    }

    private function fileForPath(string $path): string
    {
        return $this->root().DIRECTORY_SEPARATOR
            .str_replace('/', DIRECTORY_SEPARATOR, $this->keyForPath($path)).'.json';
    }

    private function keyForPath(string $path): string
    {
        return match ($path) {
            '/' => 'static/home',
            '/windows' => 'static/windows-index',
            '/doors' => 'static/doors-index',
            '/brand' => 'static/brand-index',
            '/blog' => 'static/blog-index',
            '/gallery' => 'static/gallery',
            '/glossary' => 'static/glossary',
            '/faq' => 'static/faq',
            '/testimonials' => 'static/testimonials',
            '/financing' => 'static/financing',
            '/about' => 'static/about',
            '/contacts' => 'static/contacts',
            '/special-offers' => 'static/special-offers',
            default => trim($path, '/'),
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    private function hydrate(array $data, string $requestedPath, string $baseUrl): PageMetadata
    {
        if ((int) ($data['version'] ?? 0) !== 1) {
            throw new InvalidArgumentException('Unsupported metadata version.');
        }

        $key = trim((string) ($data['key'] ?? ''));
        $path = $this->normalizePath((string) ($data['path'] ?? ''));
        if ($key !== $this->keyForPath($requestedPath) || $path !== $requestedPath) {
            throw new InvalidArgumentException('Metadata key/path does not match the requested page.');
        }

        $seo = is_array($data['seo'] ?? null) ? $data['seo'] : [];
        $title = trim((string) ($seo['title'] ?? ''));
        $description = trim((string) ($seo['description'] ?? ''));
        if ($title === '' || $description === '') {
            throw new InvalidArgumentException('SEO title and description are required.');
        }

        $canonical = trim((string) ($seo['canonical'] ?? ''));
        $canonical = $canonical !== ''
            ? $this->absoluteUrl($canonical, $baseUrl)
            : rtrim($baseUrl, '/').($path === '/' ? '' : $path);
        $canonicalPath = parse_url($canonical, PHP_URL_PATH);
        if (! filter_var($canonical, FILTER_VALIDATE_URL)
            || $this->normalizePath(is_string($canonicalPath) ? $canonicalPath : '') !== $path
        ) {
            throw new InvalidArgumentException('Canonical URL must be absolute and match the page path.');
        }

        $faq = [];
        foreach ((array) ($data['faq'] ?? []) as $index => $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException("FAQ item {$index} must be an object.");
            }
            $question = trim((string) ($item['question'] ?? ''));
            $answer = trim((string) ($item['answer'] ?? ''));
            if ($question === '' || $answer === '') {
                throw new InvalidArgumentException("FAQ item {$index} requires question and answer.");
            }
            $faq[] = ['question' => $question, 'answer' => $answer];
        }

        $schema = is_array($data['schema'] ?? null)
            ? $data['schema']
            : ['primary_type' => 'WebPage'];
        $allowedTypes = [
            'WebPage',
            'WebSite',
            'AboutPage',
            'ContactPage',
            'CollectionPage',
            'Product',
            'Service',
            'BlogPosting',
            'DefinedTermSet',
        ];
        if (! in_array((string) ($schema['primary_type'] ?? 'WebPage'), $allowedTypes, true)) {
            throw new InvalidArgumentException('Unsupported primary schema type.');
        }
        foreach ((array) ($schema['extra'] ?? []) as $extra) {
            if (! is_array($extra) || ! isset($extra['@type'])) {
                throw new InvalidArgumentException('Every schema extension requires an @type.');
            }
            $this->validateSchemaNode($extra, false);
        }
        foreach ((array) ($schema['replace'] ?? []) as $replacement) {
            if (! is_array($replacement) || ! isset($replacement['@type'])) {
                throw new InvalidArgumentException('Every replacement schema requires an @type.');
            }
            $this->validateSchemaNode($replacement, true);
        }

        $og = is_array($seo['og'] ?? null) ? $seo['og'] : [];
        $ogImage = trim((string) ($og['image'] ?? ''));

        return new PageMetadata(
            key: $key,
            path: $path,
            title: $title,
            description: $description,
            canonical: $canonical,
            ogTitle: trim((string) ($og['title'] ?? '')) ?: $title,
            ogDescription: trim((string) ($og['description'] ?? '')) ?: $description,
            ogImage: $ogImage !== '' ? $this->absoluteUrl($ogImage, $baseUrl) : null,
            ogType: trim((string) ($og['type'] ?? '')) ?: 'website',
            twitterCard: trim((string) ($seo['twitter_card'] ?? '')) ?: 'summary_large_image',
            faq: $faq,
            schema: $schema,
        );
    }

    private function normalizePath(string $path): string
    {
        $path = '/'.trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private function absoluteUrl(string $value, string $baseUrl): string
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        return rtrim($baseUrl, '/').'/'.ltrim($value, '/');
    }

    /**
     * @param array<string, mixed> $node
     */
    private function validateSchemaNode(array $node, bool $contextRequired): void
    {
        if ($contextRequired && ($node['@context'] ?? null) !== 'https://schema.org') {
            throw new InvalidArgumentException('Replacement schemas require the schema.org @context.');
        }
        if (isset($node['@context']) && $node['@context'] !== 'https://schema.org') {
            throw new InvalidArgumentException('Schema overrides must use the schema.org @context.');
        }

        foreach ($node as $key => $value) {
            if (in_array($key, ['url', '@id', 'contentUrl'], true)
                && is_string($value)
                && ! filter_var($value, FILTER_VALIDATE_URL)
            ) {
                throw new InvalidArgumentException("Schema {$key} values must be absolute URLs.");
            }
            if (is_array($value)) {
                if (array_is_list($value)) {
                    foreach ($value as $child) {
                        if (is_array($child)) {
                            $this->validateSchemaNode($child, false);
                        }
                    }
                } else {
                    $this->validateSchemaNode($value, false);
                }
            }
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

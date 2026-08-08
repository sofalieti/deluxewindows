<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;
use ZipArchive;

/**
 * Rebuild Google postal-code criteria ids into google-geo-targets.json so
 * {loc_physical_ms} ZIP ids (e.g. 9032015 = 94549 Lafayette) resolve like city ids.
 */
class BuildGoogleZipGeoTargetsCommand extends Command
{
    protected $signature = 'ads:build-google-zip-geo-targets
        {--zip-source= : Path to a geo-data.csv (state_abbr,zipcode,city). Downloads a public CA-capable list when omitted.}
        {--geotargets= : Path to Google geotargets CSV. Downloads the latest when omitted.}
        {--keep-city-zips : Also rewrite database/data/city-zips.json}';

    protected $description = 'Merge Google Ads Postal Code criteria ids for service-area cities into google-geo-targets.json';

    private const GEO_DATA_URL = 'https://raw.githubusercontent.com/scpike/us-state-county-zip/master/geo-data.csv';

    private const GEOTARGETS_ZIP_URL = 'https://developers.google.com/static/google-ads/api/data/geo/geotargets-2026-07-16.csv.zip';

    public function handle(): int
    {
        $regionsPath = database_path('data/service-area-regions.json');
        $googlePath = database_path('data/google-geo-targets.json');
        $cityZipsPath = database_path('data/city-zips.json');

        $regions = json_decode((string) file_get_contents($regionsPath), true);
        $cities = is_array($regions['cities'] ?? null) ? $regions['cities'] : [];
        if ($cities === []) {
            $this->error('No cities in service-area-regions.json');

            return self::FAILURE;
        }

        /** @var array<string, string> $slugToName */
        $slugToName = [];
        /** @var array<string, string> $nameKeyToSlug */
        $nameKeyToSlug = [];
        foreach ($cities as $slug => $row) {
            $slug = (string) $slug;
            $name = (string) ($row['name'] ?? '');
            $slugToName[$slug] = $name;
            $nameKeyToSlug[$this->nameKey($name)] = $slug;
            $nameKeyToSlug[$this->nameKey(str_replace('-', ' ', $slug))] = $slug;
        }

        // St. Helena / South San Francisco aliases + truncated geo-data labels.
        $nameKeyToSlug[$this->nameKey('Saint Helena')] = 'st-helena';
        $nameKeyToSlug[$this->nameKey('St Helena')] = 'st-helena';
        $nameKeyToSlug[$this->nameKey('South san franci')] = 'south-san-francisco';
        $nameKeyToSlug[$this->nameKey('West menlo park')] = 'menlo-park';

        $this->info('Building city → ZIP list…');
        $cityZips = $this->buildCityZips($nameKeyToSlug, $slugToName);
        $cityZips = $this->applyZipOverrides($cityZips);
        if ($cityZips === []) {
            $this->error('No ZIPs matched our service-area cities.');

            return self::FAILURE;
        }

        if ($this->option('keep-city-zips') || ! is_file($cityZipsPath)) {
            ksort($cityZips);
            file_put_contents(
                $cityZipsPath,
                json_encode($cityZips, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
            );
            $this->info('Wrote '.$cityZipsPath);
        } elseif (is_file($cityZipsPath)) {
            $existing = json_decode((string) file_get_contents($cityZipsPath), true);
            if (is_array($existing) && $existing !== []) {
                $cityZips = $existing;
            }
        }

        $wantedZips = [];
        foreach ($cityZips as $slug => $zips) {
            foreach ((array) $zips as $zip) {
                $zip = preg_replace('/\D+/', '', (string) $zip) ?: '';
                if (strlen($zip) === 5) {
                    $wantedZips[$zip] = (string) $slug;
                }
            }
        }

        $this->info(sprintf('Looking up %d ZIP codes in Google geotargets…', count($wantedZips)));

        try {
            $zipToCriteriaId = $this->loadPostalCriteriaIds(array_keys($wantedZips));
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $googleMap = json_decode((string) file_get_contents($googlePath), true);
        if (! is_array($googleMap)) {
            $googleMap = [];
        }

        // Keep existing city criteria ids; drop previous postal merges so rebuild is clean.
        // City ids for our Bay Area set are typically 1xxxxxx / 9xxxxxx but postal are also 9xxxxxx.
        // Safer: start from current map, then overwrite/add postal ids from this run.
        $merged = [];
        foreach ($googleMap as $id => $slug) {
            if (is_string($slug) && $slug !== '') {
                $merged[(string) $id] = $slug;
            }
        }

        $added = 0;
        $missingZips = [];
        foreach ($wantedZips as $zip => $slug) {
            $criteriaId = $zipToCriteriaId[$zip] ?? null;
            if ($criteriaId === null) {
                $missingZips[] = $zip.' ('.$slug.')';

                continue;
            }
            $key = (string) $criteriaId;
            if (! isset($merged[$key])) {
                $added++;
            }
            $merged[$key] = $slug;
        }

        uksort($merged, static fn ($a, $b) => (int) $a <=> (int) $b);

        file_put_contents(
            $googlePath,
            json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
        );

        $coveredCities = array_values(array_unique(array_values($wantedZips)));
        $citiesWithZip = [];
        foreach ($cityZips as $slug => $zips) {
            if ($zips !== []) {
                $citiesWithZip[] = $slug;
            }
        }
        $citiesMissingZips = array_values(array_diff(array_keys($slugToName), $citiesWithZip));

        $this->info(sprintf(
            'Wrote %s (%d criteria ids total, +%d postal). ZIPs mapped: %d / %d.',
            $googlePath,
            count($merged),
            $added,
            count($wantedZips) - count($missingZips),
            count($wantedZips)
        ));

        if ($citiesMissingZips !== []) {
            $this->warn('Cities with no ZIPs: '.implode(', ', $citiesMissingZips));
        }
        if ($missingZips !== []) {
            $this->warn('ZIPs not in Google geotargets ('.count($missingZips).'): '.implode(', ', array_slice($missingZips, 0, 20))
                .(count($missingZips) > 20 ? '…' : ''));
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $nameKeyToSlug
     * @param  array<string, string>  $slugToName
     * @return array<string, list<string>>
     */
    private function buildCityZips(array $nameKeyToSlug, array $slugToName): array
    {
        $source = (string) ($this->option('zip-source') ?: '');
        if ($source === '') {
            $source = storage_path('app/geo-data.csv');
            if (! is_file($source)) {
                $this->info('Downloading ZIP geo-data…');
                $response = Http::timeout(120)->get(self::GEO_DATA_URL);
                if (! $response->successful()) {
                    throw new \RuntimeException('Failed to download geo-data.csv HTTP '.$response->status());
                }
                file_put_contents($source, $response->body());
            }
        }

        $handle = fopen($source, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('Cannot open '.$source);
        }

        $header = fgetcsv($handle) ?: [];
        $idxAbbr = array_search('state_abbr', $header, true);
        $idxZip = array_search('zipcode', $header, true);
        $idxCity = array_search('city', $header, true);
        if ($idxAbbr === false || $idxZip === false || $idxCity === false) {
            fclose($handle);
            throw new \RuntimeException('Unexpected geo-data columns: '.implode(', ', $header));
        }

        /** @var array<string, array<string, true>> $buckets */
        $buckets = [];
        foreach (array_keys($slugToName) as $slug) {
            $buckets[$slug] = [];
        }

        while (($cols = fgetcsv($handle)) !== false) {
            if (strtoupper(trim((string) ($cols[$idxAbbr] ?? ''))) !== 'CA') {
                continue;
            }
            $city = trim((string) ($cols[$idxCity] ?? ''));
            $zip = preg_replace('/\D+/', '', (string) ($cols[$idxZip] ?? '')) ?: '';
            if ($city === '' || strlen($zip) !== 5) {
                continue;
            }
            $slug = $nameKeyToSlug[$this->nameKey($city)]
                ?? $this->fuzzySlug($city, $nameKeyToSlug);
            if ($slug === null) {
                continue;
            }
            $buckets[$slug][$zip] = true;
        }
        fclose($handle);

        $out = [];
        foreach ($buckets as $slug => $zips) {
            $list = array_keys($zips);
            sort($list, SORT_STRING);
            $out[$slug] = $list;
        }

        return $out;
    }

    /**
     * @param  list<string>  $wantedZips
     * @return array<string, int> zip => criteria id
     */
    private function loadPostalCriteriaIds(array $wantedZips): array
    {
        $wanted = array_fill_keys($wantedZips, true);
        $path = (string) ($this->option('geotargets') ?: '');

        if ($path === '') {
            $dir = storage_path('app/geotargets');
            if (! is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $zipFile = $dir.'/geotargets.csv.zip';
            $csvFile = $dir.'/geotargets.csv';

            if (! is_file($csvFile)) {
                $this->info('Downloading Google geotargets CSV…');
                $response = Http::timeout(180)->get(self::GEOTARGETS_ZIP_URL);
                if (! $response->successful()) {
                    throw new \RuntimeException('Failed to download geotargets HTTP '.$response->status());
                }
                file_put_contents($zipFile, $response->body());

                $archive = new ZipArchive;
                if ($archive->open($zipFile) !== true) {
                    throw new \RuntimeException('Could not open geotargets zip');
                }
                for ($i = 0; $i < $archive->numFiles; $i++) {
                    $name = (string) $archive->getNameIndex($i);
                    if (str_ends_with(strtolower($name), '.csv')) {
                        $stream = $archive->getStream($name);
                        if ($stream === false) {
                            continue;
                        }
                        $out = fopen($csvFile, 'wb');
                        if ($out === false) {
                            fclose($stream);
                            throw new \RuntimeException('Cannot write '.$csvFile);
                        }
                        stream_copy_to_stream($stream, $out);
                        fclose($stream);
                        fclose($out);
                        break;
                    }
                }
                $archive->close();
            }
            $path = $csvFile;
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('Cannot open '.$path);
        }

        $header = fgetcsv($handle) ?: [];
        // Google CSV uses: Criteria ID,Name,Canonical Name,Parent ID,Country Code,Target Type,Status
        $idxId = array_search('Criteria ID', $header, true);
        $idxName = array_search('Name', $header, true);
        $idxCountry = array_search('Country Code', $header, true);
        $idxType = array_search('Target Type', $header, true);
        if ($idxId === false || $idxName === false || $idxType === false) {
            fclose($handle);
            throw new \RuntimeException('Unexpected geotargets columns: '.implode(', ', $header));
        }

        $map = [];
        while (($cols = fgetcsv($handle)) !== false) {
            $type = trim((string) ($cols[$idxType] ?? ''));
            if ($type !== 'Postal Code') {
                continue;
            }
            if ($idxCountry !== false && strtoupper(trim((string) ($cols[$idxCountry] ?? ''))) !== 'US') {
                continue;
            }
            $zip = preg_replace('/\D+/', '', (string) ($cols[$idxName] ?? '')) ?: '';
            if (! isset($wanted[$zip])) {
                continue;
            }
            $id = (int) preg_replace('/\D+/', '', (string) ($cols[$idxId] ?? '')) ?: 0;
            if ($id > 0) {
                $map[$zip] = $id;
            }
        }
        fclose($handle);

        return $map;
    }

    /**
     * Hand-curated Bay Area ZIPs where public geo-data uses a neighborhood
     * label (or omits the city) instead of the municipal name.
     *
     * @param  array<string, list<string>>  $cityZips
     * @return array<string, list<string>>
     */
    private function applyZipOverrides(array $cityZips): array
    {
        $overrides = [
            'burlingame' => ['94010'],
            'cupertino' => ['95014', '95015'],
            'dixon' => ['95620'],
            'los-altos-hills' => ['94022', '94024'],
            'menlo-park' => ['94025', '94026'],
            'pittsburg' => ['94565'],
            'portola-valley' => ['94028'],
            'ross' => ['94957'],
            'sebastopol' => ['95472'],
            'south-san-francisco' => ['94080', '94083'],
            'tiburon' => ['94920'],
        ];

        foreach ($overrides as $slug => $zips) {
            $merged = array_values(array_unique(array_merge($cityZips[$slug] ?? [], $zips)));
            sort($merged, SORT_STRING);
            $cityZips[$slug] = $merged;
        }

        return $cityZips;
    }

    /**
     * @param  array<string, string>  $nameKeyToSlug
     */
    private function fuzzySlug(string $city, array $nameKeyToSlug): ?string
    {
        $key = $this->nameKey($city);
        if (strlen($key) < 8) {
            return null;
        }

        foreach ($nameKeyToSlug as $candidate => $slug) {
            if (str_starts_with($candidate, $key) || str_starts_with($key, $candidate)) {
                return $slug;
            }
        }

        return null;
    }

    private function nameKey(string $name): string
    {
        $name = Str::lower(trim($name));
        $name = str_replace(['.', "'"], '', $name);

        return (string) preg_replace('/[^a-z0-9]+/', '', $name);
    }
}

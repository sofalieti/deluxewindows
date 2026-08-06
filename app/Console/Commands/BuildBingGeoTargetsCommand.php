<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Ads\MicrosoftAdsOfflineConversionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use ReflectionClass;
use Throwable;

/**
 * Rebuild database/data/bing-geo-targets.json from Microsoft's geographical
 * locations file, aligned to our google-geo-targets.json via AdWords Location Id.
 */
class BuildBingGeoTargetsCommand extends Command
{
    protected $signature = 'ads:build-bing-geo-targets';

    protected $description = 'Download Microsoft Ads geo locations and rebuild bing-geo-targets.json for utm_city';

    public function handle(MicrosoftAdsOfflineConversionService $microsoft): int
    {
        if (! $microsoft->isConfigured()) {
            $this->error('Microsoft Ads credentials are not configured.');

            return self::FAILURE;
        }

        $googleMap = json_decode(
            (string) file_get_contents(database_path('data/google-geo-targets.json')),
            true
        );
        if (! is_array($googleMap) || $googleMap === []) {
            $this->error('google-geo-targets.json is empty.');

            return self::FAILURE;
        }

        /** @var array<int, string> $googleIdToSlug */
        $googleIdToSlug = [];
        foreach ($googleMap as $id => $slug) {
            $googleIdToSlug[(int) $id] = (string) $slug;
        }

        try {
            $ref = new ReflectionClass($microsoft);
            $tokenMethod = $ref->getMethod('accessToken');
            $tokenMethod->setAccessible(true);
            $clientMethod = $ref->getMethod('client');
            $clientMethod->setAccessible(true);

            $client = $clientMethod->invoke($microsoft, $tokenMethod->invoke($microsoft));
            $base = rtrim((string) config('services.microsoft_ads.api_base_url'), '/')
                ?: 'https://campaign.api.bingads.microsoft.com';

            $response = $client->post($base.'/CampaignManagement/v13/GeoLocationsFileUrl/Query', [
                'Version' => '2.0',
                'LanguageLocale' => 'en',
            ]);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if (! $response->successful()) {
            $this->error('GetGeoLocationsFileUrl failed HTTP '.$response->status().': '.$response->body());

            return self::FAILURE;
        }

        $fileUrl = trim((string) $response->json('FileUrl'));
        if ($fileUrl === '') {
            $this->error('No FileUrl in the Microsoft response.');

            return self::FAILURE;
        }

        $this->info('Downloading geographical locations…');
        $download = Http::timeout(120)->get($fileUrl);
        if (! $download->successful()) {
            $this->error('Download failed HTTP '.$download->status());

            return self::FAILURE;
        }

        $raw = $download->body();
        if (str_starts_with($raw, "\x1f\x8b")) {
            $decoded = gzdecode($raw);
            if ($decoded === false) {
                $this->error('Could not gunzip the locations file.');

                return self::FAILURE;
            }
            $raw = $decoded;
        }

        $lines = preg_split("/\r\n|\n|\r/", $raw) ?: [];
        $header = array_map(
            static fn ($h) => trim((string) $h),
            str_getcsv((string) array_shift($lines)) ?: []
        );

        $idxLocationId = array_search('Location Id', $header, true);
        $idxDisplay = array_search('Bing Display Name', $header, true);
        $idxType = array_search('Location Type', $header, true);
        $idxStatus = array_search('Status', $header, true);
        $idxAdWords = array_search('AdWords Location Id', $header, true);

        if ($idxLocationId === false || $idxAdWords === false || $idxType === false) {
            $this->error('Unexpected columns: '.implode(', ', $header));

            return self::FAILURE;
        }

        $bingMap = [];
        $wantedSlugs = array_values(array_unique(array_values($googleIdToSlug)));

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $cols = str_getcsv($line);
            $status = $idxStatus !== false ? trim((string) ($cols[$idxStatus] ?? '')) : 'Active';
            if ($status !== '' && ! in_array(strtolower($status), ['active', 'pending deprecation'], true)) {
                continue;
            }

            $type = trim((string) ($cols[$idxType] ?? ''));
            $bingId = (int) ($cols[$idxLocationId] ?? 0);
            $adWordsId = (int) ($cols[$idxAdWords] ?? 0);
            $display = trim((string) ($cols[$idxDisplay] ?? ''));

            if ($bingId <= 0) {
                continue;
            }

            if ($adWordsId > 0 && isset($googleIdToSlug[$adWordsId])) {
                $slug = $googleIdToSlug[$adWordsId];
                if ($type === 'City' || ! isset($bingMap[$bingId])) {
                    $bingMap[$bingId] = $slug;
                }

                continue;
            }

            if ($type === 'City' && str_contains($display, '|California|United States')) {
                $cityName = explode('|', $display)[0] ?? '';
                $slug = Str::slug($cityName);
                if (in_array($slug, $wantedSlugs, true) && ! in_array($slug, $bingMap, true)) {
                    $bingMap[$bingId] = $slug;
                }
            }
        }

        ksort($bingMap, SORT_NUMERIC);
        $out = database_path('data/bing-geo-targets.json');
        file_put_contents($out, json_encode($bingMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        $covered = array_values(array_unique(array_values($bingMap)));
        $missing = array_values(array_diff($wantedSlugs, $covered));

        $this->info(sprintf(
            'Wrote %s (%d Bing ids → %d cities).',
            $out,
            count($bingMap),
            count($covered)
        ));

        if ($missing !== []) {
            $this->warn('Missing slugs: '.implode(', ', $missing));
        }

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace App\Models\Concerns;

trait ClassifiesTrafficSource
{
    public function trafficSourceKey(): string
    {
        return $this->classifyTrafficSource($this->lastTouchAttribution());
    }

    public function firstTrafficSourceKey(): string
    {
        return $this->classifyTrafficSource($this->firstTouchAttribution());
    }

    public function trafficSourceLabel(): string
    {
        return $this->labelForTrafficSourceKey($this->trafficSourceKey());
    }

    public function firstTrafficSourceLabel(): string
    {
        return $this->labelForTrafficSourceKey($this->firstTrafficSourceKey());
    }

    public function trafficSourceColor(): string
    {
        return $this->colorForTrafficSourceKey($this->trafficSourceKey());
    }

    public function firstTrafficSourceColor(): string
    {
        return $this->colorForTrafficSourceKey($this->firstTrafficSourceKey());
    }

    public function trafficSourceDetail(): string
    {
        return $this->detailForTrafficSource($this->lastTouchAttribution(), $this->trafficSourceKey());
    }

    public function firstTrafficSourceDetail(): string
    {
        return $this->detailForTrafficSource($this->firstTouchAttribution(), $this->firstTrafficSourceKey());
    }

    /**
     * @return array{utm_source: string, utm_medium: string, utm_campaign: string, referrer: string, gclid: string, fbclid: string, msclkid: string}
     */
    protected function lastTouchAttribution(): array
    {
        return [
            'utm_source' => trim((string) ($this->utm_source ?? '')),
            'utm_medium' => trim((string) ($this->utm_medium ?? '')),
            'utm_campaign' => trim((string) ($this->utm_campaign ?? '')),
            'referrer' => $this->trafficReferrer(),
            'gclid' => $this->trafficClickId('gclid'),
            'fbclid' => $this->trafficClickId('fbclid'),
            'msclkid' => $this->trafficClickId('msclkid'),
        ];
    }

    /**
     * @return array{utm_source: string, utm_medium: string, utm_campaign: string, referrer: string, gclid: string, fbclid: string, msclkid: string}
     */
    protected function firstTouchAttribution(): array
    {
        $metaFirst = data_get($this->meta ?? [], 'first_touch');
        $metaFirst = is_array($metaFirst) ? $metaFirst : [];

        return [
            'utm_source' => $this->firstTouchValue('first_utm_source', $metaFirst, 'utm_source'),
            'utm_medium' => $this->firstTouchValue('first_utm_medium', $metaFirst, 'utm_medium'),
            'utm_campaign' => $this->firstTouchValue('first_utm_campaign', $metaFirst, 'utm_campaign'),
            'referrer' => $this->firstTouchValue('first_referrer', $metaFirst, 'referrer'),
            'gclid' => $this->firstTouchValue('first_gclid', $metaFirst, 'gclid'),
            'fbclid' => $this->firstTouchValue('first_fbclid', $metaFirst, 'fbclid'),
            'msclkid' => $this->firstTouchValue('first_msclkid', $metaFirst, 'msclkid'),
        ];
    }

    /**
     * @param  array<string, mixed>  $metaFirst
     */
    protected function firstTouchValue(string $attribute, array $metaFirst, string $metaKey): string
    {
        $direct = trim((string) ($this->getAttribute($attribute) ?? ''));
        if ($direct !== '') {
            return $direct;
        }

        $fromMeta = $metaFirst[$metaKey] ?? null;
        if ($fromMeta === null || is_array($fromMeta)) {
            return '';
        }

        return trim((string) $fromMeta);
    }

    /**
     * @param  array{utm_source: string, utm_medium: string, utm_campaign: string, referrer: string, gclid: string, fbclid: string, msclkid: string}  $touch
     */
    protected function classifyTrafficSource(array $touch): string
    {
        $source = strtolower(trim($touch['utm_source']));
        $medium = strtolower(trim($touch['utm_medium']));
        $referrerHost = $this->hostFromReferrer($touch['referrer']);
        $isPaid = preg_match('/(?:^|[_\-\s])(cpc|ppc|paid|paidsearch|paid_social|display|sem)(?:$|[_\-\s])/i', $medium) === 1;

        // Paid UTM source wins over a stale click id from another platform
        // (e.g. Bing visit still carrying an old Google _gcl_aw GCLID in storage).
        if ($isPaid && $this->isBingSource($source)) {
            return 'microsoft_ads';
        }

        if ($touch['gclid'] !== '' || $source === 'adwords' || ($this->isGoogleSource($source) && $isPaid)) {
            return 'google_ads';
        }

        if ($touch['msclkid'] !== '' || ($this->isBingSource($source) && $isPaid)) {
            return 'microsoft_ads';
        }

        if ($touch['fbclid'] !== '' && $isPaid) {
            return 'meta_ads';
        }

        if ($this->isMetaSource($source)) {
            return $isPaid ? 'meta_ads' : 'social';
        }

        if ($isPaid) {
            return 'paid_ads';
        }

        if ($this->isGoogleSource($source) || $this->isGoogleHost($referrerHost)) {
            return 'seo_google';
        }

        if ($this->isBingSource($source) || $this->hostMatches($referrerHost, 'bing.com')) {
            return 'seo_bing';
        }

        if (
            str_contains($source, 'yahoo')
            || str_contains($source, 'duckduckgo')
            || $this->hostMatches($referrerHost, 'search.yahoo.com')
            || $this->hostMatches($referrerHost, 'duckduckgo.com')
        ) {
            return 'seo_other';
        }

        if ($medium === 'email' || str_contains($source, 'email') || str_contains($source, 'newsletter')) {
            return 'email';
        }

        if ($this->isSocialHost($referrerHost)) {
            return 'social';
        }

        if ($referrerHost !== '' && ! $this->hostMatches($referrerHost, 'deluxewindows.com')) {
            return 'referral';
        }

        if ($source !== '' && ! in_array($source, ['(direct)', 'direct'], true)) {
            return 'campaign';
        }

        return 'direct';
    }

    protected function labelForTrafficSourceKey(string $key): string
    {
        return match ($key) {
            'google_ads' => 'Google Ads',
            'microsoft_ads' => 'Microsoft Ads',
            'meta_ads' => 'Meta Ads',
            'paid_ads' => 'Paid Ads',
            'seo_google' => 'SEO · Google',
            'seo_bing' => 'SEO · Bing',
            'seo_other' => 'SEO · Other',
            'social' => 'Social',
            'email' => 'Email',
            'referral' => 'Referral',
            'campaign' => 'Campaign',
            default => 'Direct',
        };
    }

    protected function colorForTrafficSourceKey(string $key): string
    {
        return match ($key) {
            'google_ads', 'microsoft_ads', 'meta_ads', 'paid_ads' => 'primary',
            'seo_google', 'seo_bing', 'seo_other' => 'success',
            'social', 'email' => 'warning',
            'referral', 'campaign' => 'info',
            default => 'secondary',
        };
    }

    /**
     * @param  array{utm_source: string, utm_medium: string, utm_campaign: string, referrer: string, gclid: string, fbclid: string, msclkid: string}  $touch
     */
    protected function detailForTrafficSource(array $touch, string $key): string
    {
        $campaign = trim($touch['utm_campaign']);
        if ($campaign !== '') {
            return $campaign;
        }

        $source = trim($touch['utm_source']);
        $medium = trim($touch['utm_medium']);
        if ($source !== '' && ! in_array(strtolower($source), ['(direct)', 'direct'], true)) {
            return $medium !== '' && $medium !== '(none)'
                ? $source.' / '.$medium
                : $source;
        }

        if ($key === 'referral') {
            return $this->hostFromReferrer($touch['referrer']);
        }

        return '';
    }

    protected function trafficClickId(string $key): string
    {
        $direct = trim((string) ($this->getAttribute($key) ?? ''));
        if ($direct !== '') {
            return $direct;
        }

        if (method_exists($this, 'metaValue')) {
            return $this->metaValue($key);
        }

        $value = data_get($this->meta ?? [], $key);
        if ($value === null || is_array($value)) {
            return '';
        }

        return trim((string) $value);
    }

    protected function trafficReferrer(): string
    {
        $direct = trim((string) ($this->getAttribute('referrer') ?? ''));
        if ($direct !== '') {
            return $direct;
        }

        if (method_exists($this, 'metaValue')) {
            return $this->metaValue('referrer');
        }

        $value = data_get($this->meta ?? [], 'referrer');
        if ($value === null || is_array($value)) {
            return '';
        }

        return trim((string) $value);
    }

    protected function hostFromReferrer(string $referrer): string
    {
        if ($referrer === '') {
            return '';
        }

        $host = parse_url($referrer, PHP_URL_HOST);
        if (! is_string($host)) {
            return '';
        }

        return preg_replace('/^www\./i', '', strtolower($host)) ?? '';
    }

    protected function isGoogleSource(string $source): bool
    {
        return $source === 'google' || str_contains($source, 'googleads') || str_contains($source, 'google_ads');
    }

    protected function isBingSource(string $source): bool
    {
        return $source === 'msn' || str_contains($source, 'bing') || str_contains($source, 'microsoft');
    }

    protected function isMetaSource(string $source): bool
    {
        return in_array($source, ['fb', 'ig'], true)
            || str_contains($source, 'facebook')
            || str_contains($source, 'instagram')
            || str_contains($source, 'meta');
    }

    protected function isGoogleHost(string $host): bool
    {
        return $host !== '' && (
            $this->hostMatches($host, 'google.com')
            || str_starts_with($host, 'google.')
            || str_contains($host, '.google.')
        );
    }

    protected function isSocialHost(string $host): bool
    {
        foreach (['facebook.com', 'instagram.com', 'linkedin.com', 't.co', 'youtube.com'] as $socialHost) {
            if ($this->hostMatches($host, $socialHost)) {
                return true;
            }
        }

        return false;
    }

    protected function hostMatches(string $host, string $domain): bool
    {
        return $host === $domain || str_ends_with($host, '.'.$domain);
    }
}

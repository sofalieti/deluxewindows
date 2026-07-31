<?php

declare(strict_types=1);

namespace App\Models\Concerns;

trait ClassifiesTrafficSource
{
    public function trafficSourceKey(): string
    {
        $source = strtolower(trim((string) ($this->utm_source ?? '')));
        $medium = strtolower(trim((string) ($this->utm_medium ?? '')));
        $referrerHost = $this->trafficReferrerHost();
        $isPaid = preg_match('/(?:^|[_\-\s])(cpc|ppc|paid|paidsearch|paid_social|display|sem)(?:$|[_\-\s])/i', $medium) === 1;

        if ($this->trafficClickId('gclid') !== '' || $source === 'adwords' || ($this->isGoogleSource($source) && $isPaid)) {
            return 'google_ads';
        }

        if ($this->trafficClickId('msclkid') !== '' || ($this->isBingSource($source) && $isPaid)) {
            return 'microsoft_ads';
        }

        if ($this->trafficClickId('fbclid') !== '' && $isPaid) {
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

    public function trafficSourceLabel(): string
    {
        return match ($this->trafficSourceKey()) {
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

    public function trafficSourceColor(): string
    {
        return match ($this->trafficSourceKey()) {
            'google_ads', 'microsoft_ads', 'meta_ads', 'paid_ads' => 'primary',
            'seo_google', 'seo_bing', 'seo_other' => 'success',
            'social', 'email' => 'warning',
            'referral', 'campaign' => 'info',
            default => 'secondary',
        };
    }

    public function trafficSourceDetail(): string
    {
        $campaign = trim((string) ($this->utm_campaign ?? ''));
        if ($campaign !== '') {
            return $campaign;
        }

        $source = trim((string) ($this->utm_source ?? ''));
        $medium = trim((string) ($this->utm_medium ?? ''));
        if ($source !== '' && ! in_array(strtolower($source), ['(direct)', 'direct'], true)) {
            return $medium !== '' && $medium !== '(none)'
                ? $source.' / '.$medium
                : $source;
        }

        if ($this->trafficSourceKey() === 'referral') {
            return $this->trafficReferrerHost();
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

    protected function trafficReferrerHost(): string
    {
        $referrer = $this->trafficReferrer();
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

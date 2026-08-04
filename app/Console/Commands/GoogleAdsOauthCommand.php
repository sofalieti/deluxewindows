<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GoogleAdsOauthCommand extends Command
{
    protected $signature = 'ads:google-oauth {--code= : Authorization code returned by Google}';

    protected $description = 'Print the Google Ads consent URL, or exchange an authorization code for a refresh token';

    public function handle(): int
    {
        $clientId = trim((string) config('services.google_ads.client_id'));
        $clientSecret = trim((string) config('services.google_ads.client_secret'));
        $redirect = trim((string) config('services.google_ads.oauth_redirect_uri'));

        if ($clientId === '' || $clientSecret === '') {
            $this->error('Set GOOGLE_ADS_CLIENT_ID and GOOGLE_ADS_CLIENT_SECRET first.');

            return self::FAILURE;
        }

        $code = trim((string) $this->option('code'));

        if ($code === '') {
            $url = 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
                'client_id' => $clientId,
                'redirect_uri' => $redirect,
                'response_type' => 'code',
                'scope' => 'https://www.googleapis.com/auth/adwords',
                'access_type' => 'offline',
                'prompt' => 'consent',
            ]);

            $this->info('1. Open this URL and approve access:');
            $this->line($url);
            $this->newLine();
            $this->info('2. Re-run with the returned code:');
            $this->line('   php artisan ads:google-oauth --code=THE_CODE');

            return self::SUCCESS;
        }

        $response = Http::asForm()->post((string) config('services.google_ads.oauth_token_url'), [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
            'redirect_uri' => $redirect,
            'grant_type' => 'authorization_code',
        ]);

        if (! $response->successful()) {
            $this->error('Token exchange failed HTTP '.$response->status().': '.$response->body());

            return self::FAILURE;
        }

        $refreshToken = trim((string) $response->json('refresh_token'));
        if ($refreshToken === '') {
            $this->error('Google returned no refresh token. Revoke prior access and retry with prompt=consent.');

            return self::FAILURE;
        }

        $this->info('Add this to your .env:');
        $this->line('GOOGLE_ADS_REFRESH_TOKEN='.$refreshToken);

        return self::SUCCESS;
    }
}

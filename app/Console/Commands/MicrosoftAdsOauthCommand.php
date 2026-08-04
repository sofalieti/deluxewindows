<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class MicrosoftAdsOauthCommand extends Command
{
    protected $signature = 'ads:microsoft-oauth {--code= : Authorization code returned by Microsoft}';

    protected $description = 'Print the Microsoft Ads consent URL, or exchange an authorization code for a refresh token';

    public function handle(): int
    {
        $clientId = trim((string) config('services.microsoft_ads.client_id'));
        $clientSecret = trim((string) config('services.microsoft_ads.client_secret'));
        $redirect = trim((string) config('services.microsoft_ads.oauth_redirect_uri'));
        $scope = 'https://ads.microsoft.com/msads.manage offline_access';

        if ($clientId === '' || $clientSecret === '') {
            $this->error('Set MICROSOFT_ADS_CLIENT_ID and MICROSOFT_ADS_CLIENT_SECRET first.');

            return self::FAILURE;
        }

        $code = trim((string) $this->option('code'));

        if ($code === '') {
            $url = (string) config('services.microsoft_ads.oauth_authorize_url').'?'.http_build_query([
                'client_id' => $clientId,
                'redirect_uri' => $redirect,
                'response_type' => 'code',
                'scope' => $scope,
                'prompt' => 'consent',
            ]);

            $this->info('1. Sign in as a Super Admin of the Microsoft Ads account and approve:');
            $this->line($url);
            $this->newLine();
            $this->info('2. Re-run with the returned code:');
            $this->line('   php artisan ads:microsoft-oauth --code=THE_CODE');

            return self::SUCCESS;
        }

        $response = Http::asForm()->post((string) config('services.microsoft_ads.oauth_token_url'), [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
            'redirect_uri' => $redirect,
            'grant_type' => 'authorization_code',
            'scope' => $scope,
        ]);

        if (! $response->successful()) {
            $this->error('Token exchange failed HTTP '.$response->status().': '.$response->body());

            return self::FAILURE;
        }

        $refreshToken = trim((string) $response->json('refresh_token'));
        if ($refreshToken === '') {
            $this->error('Microsoft returned no refresh token. Make sure offline_access is in the scope.');

            return self::FAILURE;
        }

        $this->info('Add this to your .env:');
        $this->line('MICROSOFT_ADS_REFRESH_TOKEN='.$refreshToken);

        return self::SUCCESS;
    }
}

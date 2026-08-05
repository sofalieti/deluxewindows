<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Ads\MicrosoftAdsOfflineConversionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class MicrosoftAdsOauthCommand extends Command
{
    protected $signature = 'ads:microsoft-oauth {--code= : Authorization code returned by the identity provider}';

    protected $description = 'Print the Microsoft Ads consent URL, or exchange an authorization code for a refresh token';

    public function handle(MicrosoftAdsOfflineConversionService $ads): int
    {
        $clientId = trim((string) config('services.microsoft_ads.client_id'));
        $clientSecret = trim((string) config('services.microsoft_ads.client_secret'));

        if ($clientId === '' || $clientSecret === '') {
            $this->error('Set MICROSOFT_ADS_CLIENT_ID and MICROSOFT_ADS_CLIENT_SECRET first.');

            return self::FAILURE;
        }

        $google = $ads->usesGoogleIdentity();
        $redirect = $ads->oauthRedirectUri();

        $this->line('Identity provider: '.($google ? 'Google' : 'Microsoft (Entra ID)'));
        $this->line('Redirect URI:      '.$redirect);
        $this->newLine();

        $code = trim((string) $this->option('code'));

        if ($code === '') {
            $this->line($ads->oauthAuthorizeUrl().'?'.http_build_query($google ? [
                'client_id' => $clientId,
                'redirect_uri' => $redirect,
                'response_type' => 'code',
                'scope' => $ads->oauthScope(),
                'access_type' => 'offline',
                'prompt' => 'consent',
            ] : [
                'client_id' => $clientId,
                'redirect_uri' => $redirect,
                'response_type' => 'code',
                'scope' => $ads->oauthScope(),
                'prompt' => 'consent',
            ]));
            $this->newLine();
            $this->info('1. Open the URL above and sign in as a Super Admin of the Microsoft Ads account.');
            $this->info('2. Copy the "code" parameter from the address bar you land on.');
            $this->info('3. php artisan ads:microsoft-oauth --code=THE_CODE');

            return self::SUCCESS;
        }

        $payload = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
            'redirect_uri' => $redirect,
            'grant_type' => 'authorization_code',
        ];

        if (! $google) {
            $payload['scope'] = $ads->oauthScope();
        }

        $response = Http::asForm()->post($ads->oauthTokenUrl(), $payload);

        if (! $response->successful()) {
            $this->error('Token exchange failed HTTP '.$response->status().': '.$response->body());

            return self::FAILURE;
        }

        $refreshToken = trim((string) $response->json('refresh_token'));
        if ($refreshToken === '') {
            $this->error($google
                ? 'Google returned no refresh token. Re-run the consent step with access_type=offline and prompt=consent.'
                : 'Microsoft returned no refresh token. Make sure offline_access is in the scope.');

            return self::FAILURE;
        }

        $this->info('Add this to your .env:');
        $this->line('MICROSOFT_ADS_REFRESH_TOKEN='.$refreshToken);

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Ads\GoogleLsaClient;
use Illuminate\Console\Command;
use Throwable;

class GoogleLsaOauthCommand extends Command
{
    protected $signature = 'ads:lsa-oauth
        {--code= : Authorization code from the browser address bar}
        {--write : Store the refresh token in the server .env}';

    protected $description = 'Print the Local Services consent URL, or exchange an authorization code for a refresh token';

    public function handle(GoogleLsaClient $lsa): int
    {
        if (! $lsa->hasClient()) {
            $this->error('Set GOOGLE_LSA_CLIENT_ID and GOOGLE_LSA_CLIENT_SECRET first.');

            return self::FAILURE;
        }

        $code = trim((string) $this->option('code'));

        if ($code === '') {
            $this->info('Open this URL in the Google account that owns Local Services Ads:');
            $this->line($lsa->authorizeUrl());
            $this->newLine();
            $this->line('Redirect URI: '.config('services.google_lsa.oauth_redirect_uri'));
            $this->line('After you approve access, that page stores the token and shows the account report.');

            return self::SUCCESS;
        }

        try {
            $refreshToken = $lsa->exchangeAuthorizationCode($code);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($this->option('write')) {
            GoogleLsaClient::upsertEnvFile($lsa->envPath(), [
                'GOOGLE_LSA_REFRESH_TOKEN' => $refreshToken,
            ]);
            $this->info('Stored GOOGLE_LSA_REFRESH_TOKEN in .env. Run: php artisan ads:lsa-status');

            return self::SUCCESS;
        }

        $this->info('Add this to the server .env, then run php artisan ads:lsa-status');
        $this->line('GOOGLE_LSA_REFRESH_TOKEN='.$refreshToken);

        return self::SUCCESS;
    }
}

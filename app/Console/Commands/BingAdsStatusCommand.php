<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PhoneClick;
use App\Services\Ads\MicrosoftAdsOfflineConversionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * One-shot health check for the Microsoft Ads offline conversion pipeline.
 *
 * Answers, from the server itself, the three questions that come up whenever a
 * call does not show up in Bing: are the credentials the ones we think they are,
 * does the conversion goal exist under that exact name, and how many confirmed
 * calls are still waiting to be uploaded.
 */
class BingAdsStatusCommand extends Command
{
    protected $signature = 'ads:bing-status';

    protected $description = 'Check the Microsoft Ads offline conversion setup: credentials, account, goal and pending uploads';

    public function handle(MicrosoftAdsOfflineConversionService $microsoft): int
    {
        $this->components->info('Configuration');
        $this->components->twoColumnDetail('Customer id', (string) config('services.microsoft_ads.customer_id'));
        $this->components->twoColumnDetail('Account id', (string) config('services.microsoft_ads.account_id'));
        $this->components->twoColumnDetail('Conversion goal name', (string) config('services.microsoft_ads.phone_conversion_name'));
        $this->components->twoColumnDetail('Identity provider', $microsoft->usesGoogleIdentity() ? 'Google' : 'Microsoft');
        $this->components->twoColumnDetail('Developer token', $this->mask((string) config('services.microsoft_ads.developer_token')));
        $this->components->twoColumnDetail('Refresh token', $this->mask((string) config('services.microsoft_ads.refresh_token')));

        if (! $microsoft->isConfigured()) {
            $this->components->error('Credentials are incomplete — the upload is skipped entirely.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('Account');

        try {
            $accounts = $microsoft->accounts();
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $configuredAccountId = trim((string) config('services.microsoft_ads.account_id'));
        $matched = false;

        foreach ($accounts as $account) {
            $id = trim((string) ($account['Id'] ?? ''));
            $label = sprintf(
                '%s (%s) — %s',
                $account['Name'] ?? '?',
                $account['Number'] ?? '?',
                $account['AccountLifeCycleStatus'] ?? '?'
            );
            $this->components->twoColumnDetail($id.($id === $configuredAccountId ? ' <fg=green>← configured</>' : ''), $label);
            $matched = $matched || $id === $configuredAccountId;
        }

        if (! $matched) {
            $this->components->error('The configured account id is not in this customer — uploads will be rejected.');
        }

        $this->newLine();
        $this->components->info('Offline conversion goals');

        try {
            $goals = $microsoft->offlineConversionGoals();
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($goals === []) {
            $this->components->error('No offline conversion goal exists on this account.');
        }

        $expected = trim((string) config('services.microsoft_ads.phone_conversion_name'));
        $goalFound = false;

        foreach ($goals as $goal) {
            $name = trim((string) ($goal['Name'] ?? ''));
            $goalFound = $goalFound || $name === $expected;

            $this->components->twoColumnDetail(
                $name.($name === $expected ? ' <fg=green>← configured</>' : ''),
                (string) ($goal['Status'] ?? '?')
            );
            $this->line(sprintf(
                '    scope %s · count %s · window %d days · tracking %s',
                $goal['Scope'] ?? '?',
                $goal['CountType'] ?? '?',
                (int) (($goal['ConversionWindowInMinutes'] ?? 0) / 1440),
                $goal['TrackingStatus'] ?? '?'
            ));
        }

        if ($goals !== [] && ! $goalFound) {
            $this->components->error(sprintf(
                'No goal is named "%s" — Microsoft rejects every upload with OfflineConversionNameInvalid.',
                $expected
            ));
        }

        $this->newLine();
        $this->components->info('Confirmed calls');
        $this->reportPendingClicks();

        return self::SUCCESS;
    }

    private function reportPendingClicks(): void
    {
        if (! Schema::hasColumn('phone_clicks', 'bing_ads_conversion_sent_at')) {
            $this->components->warn('Run php artisan migrate — the offline conversion columns are missing.');

            return;
        }

        $confirmed = PhoneClick::query()
            ->notSpam()
            ->where('ringcentral_status', PhoneClick::RINGCENTRAL_FOUND)
            ->where('created_at', '>=', now()->subDays(MicrosoftAdsOfflineConversionService::IMPORT_WINDOW_DAYS))
            ->where(function ($query): void {
                $query->whereNotNull('msclkid')->where('msclkid', '!=', '')
                    ->orWhere(function ($first): void {
                        $first->whereNotNull('first_msclkid')->where('first_msclkid', '!=', '');
                    });
            });

        $sent = (clone $confirmed)->whereNotNull('bing_ads_conversion_sent_at')->count();
        $pending = (clone $confirmed)->whereNull('bing_ads_conversion_sent_at')->count();
        $failed = (clone $confirmed)
            ->whereNull('bing_ads_conversion_sent_at')
            ->whereNotNull('bing_ads_conversion_error')
            ->count();

        $this->components->twoColumnDetail('Uploaded', (string) $sent);
        $this->components->twoColumnDetail('Waiting to upload', (string) $pending);
        $this->components->twoColumnDetail('Failed at least once', (string) $failed);

        $latest = (clone $confirmed)
            ->whereNotNull('bing_ads_conversion_error')
            ->orderByDesc('id')
            ->first(['id', 'bing_ads_conversion_error']);

        if ($latest !== null) {
            $this->newLine();
            $this->components->warn('Last error (phone click #'.$latest->id.'):');
            $this->line('  '.$latest->bing_ads_conversion_error);
        }
    }

    private function mask(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '<fg=red>missing</>';
        }

        return strlen($value) <= 8
            ? str_repeat('*', strlen($value))
            : substr($value, 0, 4).str_repeat('*', 6).substr($value, -4);
    }
}

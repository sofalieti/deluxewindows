<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Ads\GoogleLsaClient;
use Illuminate\Console\Command;
use Throwable;

class GoogleLsaStatusCommand extends Command
{
    protected $signature = 'ads:lsa-status';

    protected $description = 'Check Local Services Ads credentials and print this week’s account report';

    public function handle(GoogleLsaClient $lsa): int
    {
        $this->components->info('Credentials');
        $this->components->twoColumnDetail('Client id', $this->mask((string) config('services.google_lsa.client_id')));
        $this->components->twoColumnDetail('Client secret', $this->mask((string) config('services.google_lsa.client_secret')));
        $this->components->twoColumnDetail('Refresh token', $this->mask((string) config('services.google_lsa.refresh_token')));
        $this->components->twoColumnDetail('Manager customer id', $lsa->managerCustomerId() !== '' ? $lsa->managerCustomerId() : 'missing');

        if (! $lsa->hasClient() || ! $lsa->hasRefreshToken()) {
            $this->components->error('Credentials are incomplete. Run php artisan ads:lsa-oauth on the server.');

            return self::FAILURE;
        }

        if ($lsa->managerCustomerId() === '') {
            $this->components->error('Set GOOGLE_LSA_MANAGER_CUSTOMER_ID to the 10-digit Google Ads manager id.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('Account report, last 7 days');

        try {
            $accounts = $lsa->accountReports();
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($accounts === []) {
            $this->components->warn('The API answered, and this manager has no Local Services accounts in the range.');
        }

        foreach ($accounts as $account) {
            $name = (string) ($account['businessName'] ?? $account['accountId'] ?? '?');
            $this->line(sprintf(
                '%s — charged leads %s, phone calls %s, cost %s',
                $name,
                $account['currentPeriodChargedLeads'] ?? '0',
                $account['currentPeriodPhoneCalls'] ?? '0',
                $account['currentPeriodTotalCost'] ?? '0'
            ));
        }

        $this->newLine();
        $this->components->info('Recent leads');

        try {
            $leads = $lsa->detailedLeadReports();
        } catch (Throwable $exception) {
            $this->components->warn($exception->getMessage());

            return self::SUCCESS;
        }

        $this->components->twoColumnDetail('Leads returned', (string) count($leads));

        foreach (array_slice($leads, 0, 5) as $lead) {
            $this->components->twoColumnDetail(
                (string) ($lead['leadType'] ?? 'lead'),
                trim(((string) ($lead['chargeStatus'] ?? '')).' '.((string) ($lead['leadCreationTimestamp'] ?? '')))
            );
        }

        return self::SUCCESS;
    }

    private function mask(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return 'missing';
        }

        if (strlen($value) <= 8) {
            return 'set';
        }

        return substr($value, 0, 4).'…'.substr($value, -4);
    }
}

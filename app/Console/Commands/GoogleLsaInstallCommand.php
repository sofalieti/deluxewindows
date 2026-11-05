<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Ads\GoogleLsaClient;
use Illuminate\Console\Command;
use Throwable;

class GoogleLsaInstallCommand extends Command
{
    protected $signature = 'ads:lsa-install';

    protected $description = 'Copy Google LSA credentials from the process environment into .env';

    public function handle(): int
    {
        $pairs = [];

        foreach ([
            'GOOGLE_LSA_CLIENT_ID',
            'GOOGLE_LSA_CLIENT_SECRET',
            'GOOGLE_LSA_REFRESH_TOKEN',
            'GOOGLE_LSA_MANAGER_CUSTOMER_ID',
        ] as $key) {
            $value = getenv($key);
            if (is_string($value) && trim($value) !== '') {
                $pairs[$key] = trim($value);
            }
        }

        if ($pairs === []) {
            $this->info('No Google LSA environment values to store.');

            return self::SUCCESS;
        }

        try {
            GoogleLsaClient::upsertEnvFile(base_path('.env'), $pairs);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        foreach (array_keys($pairs) as $key) {
            $this->line($key.' stored');
        }

        return self::SUCCESS;
    }
}

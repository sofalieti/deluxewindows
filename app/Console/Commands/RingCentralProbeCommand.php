<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PromotionControlService;
use App\Services\RingCentralCallLogService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use ReflectionMethod;

class RingCentralProbeCommand extends Command
{
    protected $signature = 'ringcentral:probe
                            {--days=7 : How many days back to query}
                            {--phone= : Override business phone (E.164 or digits)}';

    protected $description = 'Probe RingCentral call-log API and report raw vs normalized call counts';

    public function handle(
        RingCentralCallLogService $callLog,
        PromotionControlService $promotions,
    ): int {
        $cfg = config('services.ringcentral');
        $clientId = trim((string) ($cfg['client_id'] ?? ''));
        $clientSecret = trim((string) ($cfg['client_secret'] ?? ''));
        $jwt = trim((string) ($cfg['jwt'] ?? ''));
        $base = rtrim((string) ($cfg['base_url'] ?? 'https://platform.ringcentral.com'), '/');
        $accountId = trim((string) ($cfg['account_id'] ?? '~')) ?: '~';
        $days = max(1, (int) $this->option('days'));

        if ($clientId === '' || $clientSecret === '' || $jwt === '') {
            $this->error('RINGCENTRAL_CLIENT_ID / CLIENT_SECRET / JWT are not configured.');

            return self::FAILURE;
        }

        $tokenResponse = Http::asForm()
            ->acceptJson()
            ->withBasicAuth($clientId, $clientSecret)
            ->timeout(30)
            ->post($base.'/restapi/oauth/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

        if (! $tokenResponse->successful()) {
            $this->error('Auth failed HTTP '.$tokenResponse->status());
            $this->line(substr($tokenResponse->body(), 0, 400));

            return self::FAILURE;
        }

        $token = (string) $tokenResponse->json('access_token');
        $now = CarbonImmutable::now('UTC');
        $dateFrom = $now->subDays($days);
        $url = $base.'/restapi/v1.0/account/'.$accountId.'/call-log';

        $this->info("Window: {$dateFrom->toIso8601String()} → {$now->toIso8601String()}");

        // Paginate account-wide (this is what works on this account).
        $wideRecords = [];
        $pageUrl = $url;
        $pageQuery = [
            'view' => 'Simple',
            'type' => 'Voice',
            'dateFrom' => $dateFrom->format('Y-m-d\TH:i:s.v\Z'),
            'dateTo' => $now->format('Y-m-d\TH:i:s.v\Z'),
            'perPage' => 100,
        ];
        $page = 0;
        $firstPaging = null;

        do {
            $wide = Http::withToken($token)->acceptJson()->timeout(30)->get($pageUrl, $pageQuery);
            if ($page === 0) {
                $this->line('Account-wide HTTP '.$wide->status());
                $firstPaging = $wide->json('paging', []);
            }
            if (! $wide->successful()) {
                $this->error('Account-wide failed: '.substr($wide->body(), 0, 400));

                return self::FAILURE;
            }

            $pageRecords = $wide->json('records', []);
            if (is_array($pageRecords)) {
                foreach ($pageRecords as $record) {
                    if (is_array($record)) {
                        $wideRecords[] = $record;
                    }
                }
            }

            $nextUrl = trim((string) $wide->json('navigation.nextPage.uri', ''));
            $pageUrl = $nextUrl;
            $pageQuery = [];
            $page++;
        } while ($pageUrl !== '' && $page < 100);

        $this->line('Account-wide total records (all pages): '.count($wideRecords));
        $this->line('First-page paging: '.json_encode($firstPaging));

        foreach (array_slice($wideRecords, 0, 8) as $i => $record) {
            $this->line(sprintf(
                '  [%d] %s %s from=%s to=%s start=%s result=%s',
                $i,
                (string) ($record['direction'] ?? ''),
                (string) ($record['id'] ?? ''),
                (string) data_get($record, 'from.phoneNumber', ''),
                (string) data_get($record, 'to.phoneNumber', ''),
                (string) ($record['startTime'] ?? ''),
                (string) ($record['result'] ?? ''),
            ));
        }

        $usesAccountWide = $this->fetchCallsUsesAccountWide($callLog);
        $this->newLine();
        $this->line('fetchCalls mode: '.($usesAccountWide
            ? 'account-wide + local filter (good)'
            : 'API phoneNumber filter (BROKEN on this account — deploy latest code)'));

        $phones = [];
        $override = trim((string) $this->option('phone'));
        if ($override !== '') {
            $phones[] = $override;
        } else {
            $phones = $promotions->ringCentralPhones();
        }

        if ($phones === []) {
            $this->warn('No monitored phones configured in Promotions.');

            return self::SUCCESS;
        }

        foreach ($phones as $phone) {
            $normalized = $callLog->normalizePhone($phone);
            $this->newLine();
            $this->info("Phone {$phone} → {$normalized}");

            $apiFiltered = Http::withToken($token)->acceptJson()->timeout(30)->get($url, [
                'view' => 'Simple',
                'type' => 'Voice',
                'phoneNumber' => $normalized,
                'dateFrom' => $dateFrom->format('Y-m-d\TH:i:s.v\Z'),
                'dateTo' => $now->format('Y-m-d\TH:i:s.v\Z'),
                'perPage' => 100,
            ]);
            $apiCount = is_array($apiFiltered->json('records')) ? count($apiFiltered->json('records')) : 0;
            $this->line("API phoneNumber= filter: HTTP {$apiFiltered->status()} count={$apiCount} (expect 0 on this account)");

            $localKept = [];
            foreach ($wideRecords as $record) {
                $call = $callLog->normalizeCallRecord($record, $normalized);
                if ($call !== null) {
                    $localKept[$call['ringcentral_call_id']] = $call;
                }
            }
            $this->line('Local filter from account-wide: '.count($localKept));
            foreach (array_slice(array_values($localKept), 0, 5) as $i => $call) {
                $this->line(sprintf(
                    '  local[%d] %s external=%s start=%s result=%s',
                    $i,
                    $call['direction'],
                    $call['external_phone'],
                    $call['started_at']->toIso8601String(),
                    $call['result'],
                ));
            }

            try {
                $kept = $callLog->fetchCalls($normalized, $dateFrom, $now);
                $this->line('fetchCalls(): '.count($kept).($usesAccountWide ? '' : '  ← still broken until deploy'));
            } catch (\Throwable $e) {
                $this->error('fetchCalls: '.$e->getMessage());
            }
        }

        $this->newLine();
        $this->comment('Next: deploy latest sync code, then: php artisan ringcentral:sync-calls --days=7');

        return self::SUCCESS;
    }

    private function fetchCallsUsesAccountWide(RingCentralCallLogService $callLog): bool
    {
        try {
            $method = new ReflectionMethod($callLog, 'fetchCalls');
            $file = file_get_contents($method->getFileName() ?: '');
            if (! is_string($file) || $file === '') {
                return method_exists($callLog, 'fetchAccountVoiceRecords');
            }

            return str_contains($file, 'fetchAccountVoiceRecords')
                && method_exists($callLog, 'fetchAccountVoiceRecords');
        } catch (\Throwable) {
            return method_exists($callLog, 'fetchAccountVoiceRecords');
        }
    }
}

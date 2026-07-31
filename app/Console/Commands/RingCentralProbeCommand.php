<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PromotionControlService;
use App\Services\RingCentralCallLogService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

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

        $wide = Http::withToken($token)->acceptJson()->timeout(30)->get($url, [
            'view' => 'Simple',
            'type' => 'Voice',
            'dateFrom' => $dateFrom->format('Y-m-d\TH:i:s.v\Z'),
            'dateTo' => $now->format('Y-m-d\TH:i:s.v\Z'),
            'perPage' => 100,
        ]);

        $this->line('Account-wide HTTP '.$wide->status());
        $wideRecords = $wide->json('records', []);
        $paging = $wide->json('paging', []);
        $this->line('Account-wide page records: '.(is_array($wideRecords) ? count($wideRecords) : 0));
        $this->line('Paging: '.json_encode($paging));

        if (is_array($wideRecords)) {
            foreach (array_slice($wideRecords, 0, 8) as $i => $record) {
                if (! is_array($record)) {
                    continue;
                }
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
        }

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

            $raw = Http::withToken($token)->acceptJson()->timeout(30)->get($url, [
                'view' => 'Simple',
                'type' => 'Voice',
                'phoneNumber' => $normalized,
                'dateFrom' => $dateFrom->format('Y-m-d\TH:i:s.v\Z'),
                'dateTo' => $now->format('Y-m-d\TH:i:s.v\Z'),
                'perPage' => 100,
            ]);

            $rawRecords = $raw->json('records', []);
            $this->line('Raw filtered HTTP '.$raw->status().' count='.(is_array($rawRecords) ? count($rawRecords) : 0));
            $this->line('Raw paging: '.json_encode($raw->json('paging', [])));

            if (is_array($rawRecords)) {
                foreach (array_slice($rawRecords, 0, 5) as $i => $record) {
                    if (! is_array($record)) {
                        continue;
                    }
                    $this->line(sprintf(
                        '  raw[%d] %s from=%s to=%s start=%s',
                        $i,
                        (string) ($record['direction'] ?? ''),
                        (string) data_get($record, 'from.phoneNumber', ''),
                        (string) data_get($record, 'to.phoneNumber', ''),
                        (string) ($record['startTime'] ?? ''),
                    ));
                }
            }

            try {
                $kept = $callLog->fetchCalls($normalized, $dateFrom, $now);
                $this->line('After our normalizer: '.count($kept));
                foreach (array_slice($kept, 0, 5) as $i => $call) {
                    $this->line(sprintf(
                        '  kept[%d] %s external=%s start=%s result=%s',
                        $i,
                        $call['direction'],
                        $call['external_phone'],
                        $call['started_at']->toIso8601String(),
                        $call['result'],
                    ));
                }
            } catch (\Throwable $e) {
                $this->error('fetchCalls: '.$e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}

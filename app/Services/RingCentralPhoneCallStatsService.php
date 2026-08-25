<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Models\RingCentralCall;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class RingCentralPhoneCallStatsService
{
    /**
     * @param  iterable<int, string|null>  $phones
     * @return array<string, array{inbound: int, outbound: int, connected: bool, connected_count: int}>
     */
    public function statsForPhones(iterable $phones): array
    {
        $wanted = [];
        foreach ($phones as $phone) {
            $key = $this->phoneKey($phone);
            if ($key !== null) {
                $wanted[$key] = true;
            }
        }

        if ($wanted === [] || ! Schema::hasTable('ringcentral_calls')) {
            return [];
        }

        $keys = array_keys($wanted);
        $calls = RingCentralCall::query()
            ->visible()
            ->where(function ($query) use ($keys): void {
                foreach ($keys as $index => $last10) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $query->{$method}('external_phone', 'like', '%'.$last10);
                }
            })
            ->get(['direction', 'result', 'duration', 'external_phone']);

        $stats = [];
        foreach ($keys as $key) {
            $stats[$key] = $this->emptyStats();
        }

        foreach ($calls as $call) {
            $key = $this->phoneKey((string) $call->external_phone);
            if ($key === null || ! isset($stats[$key])) {
                continue;
            }

            if (strcasecmp((string) $call->direction, 'Inbound') === 0) {
                $stats[$key]['inbound']++;
            } elseif (strcasecmp((string) $call->direction, 'Outbound') === 0) {
                $stats[$key]['outbound']++;
            }

            if ($this->isConnectedCall($call)) {
                $stats[$key]['connected'] = true;
                $stats[$key]['connected_count']++;
            }
        }

        return $stats;
    }

    /**
     * @param  Collection<int, object>|iterable<int, object>  $models
     * @return array<string, array{inbound: int, outbound: int, connected: bool, connected_count: int}>
     */
    public function statsForModels(iterable $models, string $phoneAttribute = 'phone'): array
    {
        $phones = [];
        foreach ($models as $model) {
            $phones[] = (string) data_get($model, $phoneAttribute, '');
        }

        return $this->statsForPhones($phones);
    }

    /**
     * @return array{inbound: int, outbound: int, connected: bool, connected_count: int}
     */
    public function statsForPhone(?string $phone): array
    {
        $key = $this->phoneKey($phone);
        if ($key === null) {
            return $this->emptyStats();
        }

        return $this->statsForPhones([$phone])[$key] ?? $this->emptyStats();
    }

    /**
     * @param  array<string, array{inbound: int, outbound: int, connected: bool, connected_count: int}>  $statsByPhone
     * @return array{inbound: int, outbound: int, connected: bool, connected_count: int}
     */
    public function lookup(array $statsByPhone, ?string $phone): array
    {
        $key = $this->phoneKey($phone);

        return $key !== null && isset($statsByPhone[$key])
            ? $statsByPhone[$key]
            : $this->emptyStats();
    }

    public function phoneKey(?string $phone): ?string
    {
        $digits = Contact::normalizePhone($phone);
        if ($digits === null) {
            return null;
        }

        $last10 = substr($digits, -10);

        return strlen($last10) === 10 ? $last10 : null;
    }

    /**
     * @return array{inbound: int, outbound: int, connected: bool, connected_count: int}
     */
    public function emptyStats(): array
    {
        return [
            'inbound' => 0,
            'outbound' => 0,
            'connected' => false,
            'connected_count' => 0,
        ];
    }

    public function isConnectedCall(RingCentralCall $call): bool
    {
        return $this->isConnectedResult((string) ($call->result ?? ''), (int) $call->duration);
    }

    public function isConnectedResult(string $result, int $duration): bool
    {
        $normalized = strtolower(trim($result));
        if ($normalized === '') {
            return $duration >= 20;
        }

        foreach (['missed', 'voicemail', 'no answer', 'busy', 'rejected', 'blocked', 'hang up', 'cancelled', 'canceled'] as $missed) {
            if (str_contains($normalized, $missed)) {
                return false;
            }
        }

        foreach (['accepted', 'call connected', 'answered', 'connected', 'received'] as $connected) {
            if (str_contains($normalized, $connected)) {
                return true;
            }
        }

        return $duration >= 20;
    }
}

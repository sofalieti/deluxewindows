<?php

declare(strict_types=1);

namespace App\Services\Ads;

use App\Models\PhoneClick;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

/**
 * Aggregate offline-conversion upload health for the Phone clicks admin screen.
 */
class OfflineConversionStatsService
{
    public const WINDOW_DAYS = 90;

    /**
     * @return array{
     *     bing: array{uploaded: int, waiting: int, failed: int, last_sent_at: ?CarbonInterface, last_sent_label: string, last_error: ?string, last_error_click_id: ?int},
     *     google: array{uploaded: int, waiting: int, failed: int, last_sent_at: ?CarbonInterface, last_sent_label: string, last_error: ?string, last_error_click_id: ?int}
     * }
     */
    public function summary(): array
    {
        return [
            'bing' => $this->platformStats(
                sentColumn: 'bing_ads_conversion_sent_at',
                errorColumn: 'bing_ads_conversion_error',
                clickIdColumns: ['msclkid', 'first_msclkid'],
            ),
            'google' => $this->platformStats(
                sentColumn: 'google_ads_conversion_sent_at',
                errorColumn: 'google_ads_conversion_error',
                clickIdColumns: ['gclid', 'first_gclid'],
            ),
        ];
    }

    /**
     * @param  list<string>  $clickIdColumns
     * @return array{uploaded: int, waiting: int, failed: int, last_sent_at: ?CarbonInterface, last_sent_label: string, last_error: ?string, last_error_click_id: ?int}
     */
    private function platformStats(string $sentColumn, string $errorColumn, array $clickIdColumns): array
    {
        $empty = [
            'uploaded' => 0,
            'waiting' => 0,
            'failed' => 0,
            'last_sent_at' => null,
            'last_sent_label' => 'never',
            'last_error' => null,
            'last_error_click_id' => null,
        ];

        if (
            ! Schema::hasTable('phone_clicks')
            || ! Schema::hasColumn('phone_clicks', $sentColumn)
            || ! Schema::hasColumn('phone_clicks', $errorColumn)
        ) {
            return $empty;
        }

        $confirmed = $this->eligibleQuery($clickIdColumns);

        $uploaded = (clone $confirmed)->whereNotNull($sentColumn)->count();
        $waiting = (clone $confirmed)->whereNull($sentColumn)->count();
        $failed = (clone $confirmed)
            ->whereNull($sentColumn)
            ->whereNotNull($errorColumn)
            ->count();

        $lastSentAt = (clone $confirmed)
            ->whereNotNull($sentColumn)
            ->orderByDesc($sentColumn)
            ->value($sentColumn);

        $lastSent = $lastSentAt !== null
            ? \Illuminate\Support\Carbon::parse($lastSentAt)
            : null;

        $latestError = (clone $confirmed)
            ->whereNull($sentColumn)
            ->whereNotNull($errorColumn)
            ->orderByDesc('id')
            ->first(['id', $errorColumn]);

        return [
            'uploaded' => $uploaded,
            'waiting' => $waiting,
            'failed' => $failed,
            'last_sent_at' => $lastSent,
            'last_sent_label' => $this->formatPacific($lastSent),
            'last_error' => $latestError !== null
                ? trim((string) $latestError->{$errorColumn})
                : null,
            'last_error_click_id' => $latestError?->id,
        ];
    }

    /**
     * @param  list<string>  $clickIdColumns
     * @return Builder<PhoneClick>
     */
    private function eligibleQuery(array $clickIdColumns): Builder
    {
        return PhoneClick::query()
            ->notSpam()
            ->where('ringcentral_status', PhoneClick::RINGCENTRAL_FOUND)
            ->where('created_at', '>=', now()->subDays(self::WINDOW_DAYS))
            ->where(function (Builder $query) use ($clickIdColumns): void {
                foreach ($clickIdColumns as $index => $column) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $query->{$method}(function (Builder $inner) use ($column): void {
                        $inner->whereNotNull($column)->where($column, '!=', '');
                    });
                }
            });
    }

    private function formatPacific(?CarbonInterface $at): string
    {
        if ($at === null) {
            return 'never';
        }

        return $at->copy()->timezone('America/Los_Angeles')->format('M j, Y g:i A').' PT';
    }
}

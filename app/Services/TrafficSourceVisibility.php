<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Concerns\ClassifiesTrafficSource;
use App\Models\Lead;
use App\Models\PhoneClick;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Restricts which leads / phone clicks a user may see, by last-touch traffic bucket.
 *
 * Buckets map onto the finer keys from ClassifiesTrafficSource so Roles can grant
 * "SEO" or "Bing" without listing every internal key.
 */
class TrafficSourceVisibility
{
    public const SECTION_LEADS = 'leads';

    public const SECTION_PHONE_CLICKS = 'phone-clicks';

    /** @var list<string> */
    public const BUCKETS = ['adwords', 'bing', 'seo', 'direct', 'other'];

    /**
     * @return array<string, string> bucket => permission label
     */
    public static function bucketLabels(): array
    {
        return [
            'adwords' => 'AdWords / Google Ads',
            'bing' => 'Bing / Microsoft Ads',
            'seo' => 'SEO',
            'direct' => 'Direct',
            'other' => 'Other (Meta, referral, email…)',
        ];
    }

    public static function permission(string $section, string $bucket): string
    {
        return 'platform.'.$section.'.source.'.$bucket;
    }

    /**
     * @return list<string>
     */
    public static function keysForBucket(string $bucket): array
    {
        return match ($bucket) {
            'adwords' => ['google_ads'],
            'bing' => ['microsoft_ads'],
            'seo' => ['seo_google', 'seo_bing', 'seo_other'],
            'direct' => ['direct'],
            'other' => ['meta_ads', 'paid_ads', 'social', 'email', 'referral', 'campaign'],
            default => [],
        };
    }

    public static function bucketForKey(string $key): string
    {
        return match ($key) {
            'google_ads' => 'adwords',
            'microsoft_ads' => 'bing',
            'seo_google', 'seo_bing', 'seo_other' => 'seo',
            'direct' => 'direct',
            default => 'other',
        };
    }

    /**
     * Source keys the user may see for a section. Empty means see nothing.
     *
     * @return list<string>
     */
    public function allowedSourceKeys(?Authenticatable $user, string $section): array
    {
        if (! $user instanceof User) {
            return [];
        }

        $keys = [];

        foreach (self::BUCKETS as $bucket) {
            if ($user->hasAccess(self::permission($section, $bucket))) {
                array_push($keys, ...self::keysForBucket($bucket));
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * @return list<string>
     */
    public function allowedBucketLabels(?Authenticatable $user, string $section): array
    {
        if (! $user instanceof User) {
            return [];
        }

        $labels = self::bucketLabels();
        $allowed = [];

        foreach (self::BUCKETS as $bucket) {
            if ($user->hasAccess(self::permission($section, $bucket))) {
                $allowed[] = $labels[$bucket];
            }
        }

        return $allowed;
    }

    public function canView(?Authenticatable $user, Model $record, string $section): bool
    {
        $key = $this->sourceKeyFor($record);
        $allowed = $this->allowedSourceKeys($user, $section);

        return in_array($key, $allowed, true);
    }

    public function authorizeOrAbort(?Authenticatable $user, Model $record, string $section): void
    {
        abort_unless($this->canView($user, $record, $section), 403);
    }

    /**
     * @param  Builder<Lead>|Builder<PhoneClick>  $query
     * @return Builder<Lead>|Builder<PhoneClick>
     */
    public function constrain(Builder $query, ?Authenticatable $user, string $section): Builder
    {
        $keys = $this->allowedSourceKeys($user, $section);

        if ($keys === []) {
            return $query->whereRaw('0 = 1');
        }

        $table = $query->getModel()->getTable();

        return $query->whereIn($table.'.traffic_source', $keys);
    }

    public function sourceKeyFor(Model $record): string
    {
        if (in_array(ClassifiesTrafficSource::class, class_uses_recursive($record), true)
            && method_exists($record, 'trafficSourceKey')
        ) {
            $stored = trim((string) ($record->getAttribute('traffic_source') ?? ''));
            if ($stored !== '') {
                return $stored;
            }

            return $record->trafficSourceKey();
        }

        return 'direct';
    }

    /**
     * Permissions to grant so existing admins keep full visibility after deploy.
     *
     * @return array<string, bool>
     */
    public static function allGrantPayload(): array
    {
        return array_merge(
            ['platform.phone-clicks' => true],
            self::sourceGrantPayload(self::SECTION_LEADS),
            self::sourceGrantPayload(self::SECTION_PHONE_CLICKS),
        );
    }

    /**
     * @return array<string, bool>
     */
    public static function sourceGrantPayload(string $section): array
    {
        $permissions = [];

        foreach (self::BUCKETS as $bucket) {
            $permissions[self::permission($section, $bucket)] = true;
        }

        return $permissions;
    }
}

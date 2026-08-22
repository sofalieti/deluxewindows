<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;
use Orchid\Screen\AsSource;

class ReferralPartner extends Model
{
    use AsSource;
    use Filterable;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_REJECTED = 'rejected';

    /**
     * @var array<string, string>
     */
    public const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_PAUSED => 'Paused',
        self::STATUS_REJECTED => 'Rejected',
    ];

    protected $fillable = [
        'user_id',
        'application_id',
        'code',
        'name',
        'email',
        'phone',
        'status',
        'notes',
        'payout_details',
    ];

    /**
     * @var array
     */
    protected $allowedFilters = [
        'id' => Where::class,
        'code' => Like::class,
        'email' => Like::class,
        'name' => Like::class,
        'status' => Where::class,
    ];

    protected $allowedSorts = [
        'id',
        'created_at',
        'name',
        'email',
        'status',
        'code',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(ReferralApplication::class, 'application_id');
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(ReferralReward::class, 'partner_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'referral_partner_id');
    }

    public function phoneClicks(): HasMany
    {
        return $this->hasMany(PhoneClick::class, 'referral_partner_id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(SiteVisit::class, 'referral_partner_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function referralUrl(): string
    {
        return url('/r/'.$this->code);
    }

    public function campaignUrl(): string
    {
        return url('/?utm_source=referral&utm_medium=partner&utm_campaign='.rawurlencode($this->code));
    }

    public static function generateUniqueCode(string $seed = ''): string
    {
        $base = Str::slug($seed);
        $base = $base !== '' ? Str::limit($base, 24, '') : 'partner';
        $code = $base;
        $i = 0;

        while (static::query()->where('code', $code)->exists()) {
            $i++;
            $code = $base.'-'.$i;
        }

        return $code;
    }
}

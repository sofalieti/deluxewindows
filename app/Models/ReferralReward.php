<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Where;
use Orchid\Screen\AsSource;

class ReferralReward extends Model
{
    use AsSource;
    use Filterable;

    public const STATUS_ELIGIBLE = 'eligible';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PAID = 'paid';

    public const STATUS_REJECTED = 'rejected';

    public const DEFAULT_AMOUNT_CENTS = 15000;

    /**
     * @var array<string, string>
     */
    public const STATUSES = [
        self::STATUS_ELIGIBLE => 'Eligible',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_PAID => 'Paid',
        self::STATUS_REJECTED => 'Rejected',
    ];

    protected $fillable = [
        'partner_id',
        'lead_id',
        'amount_cents',
        'status',
        'eligible_at',
        'approved_at',
        'approved_by',
        'paid_at',
        'paid_by',
        'rejected_at',
        'admin_note',
    ];

    /**
     * @var array
     */
    protected $allowedFilters = [
        'id' => Where::class,
        'partner_id' => Where::class,
        'lead_id' => Where::class,
        'status' => Where::class,
    ];

    protected $allowedSorts = [
        'id',
        'created_at',
        'status',
        'amount_cents',
        'eligible_at',
        'approved_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'eligible_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(ReferralPartner::class, 'partner_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function paidByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function amountDollars(): float
    {
        return round($this->amount_cents / 100, 2);
    }

    public function amountLabel(): string
    {
        return '$'.number_format($this->amountDollars(), 2);
    }
}

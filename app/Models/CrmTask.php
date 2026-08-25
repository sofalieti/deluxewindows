<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Where;
use Orchid\Screen\AsSource;

class CrmTask extends Model
{
    use AsSource;
    use Filterable;

    public const PERMISSION = 'platform.crm.tasks';

    public const PERMISSION_ALL = 'platform.crm.tasks.all';

    public const STATUS_OPEN = 'open';

    public const STATUS_DONE = 'done';

    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_CALL = 'call';

    public const TYPE_CALLBACK = 'callback';

    public const TYPE_MEETING = 'meeting';

    public const TYPE_QUOTE = 'quote';

    public const TYPE_FOLLOWUP = 'followup';

    public const TYPE_OTHER = 'other';

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_NORMAL = 'normal';

    public const PRIORITY_HIGH = 'high';

    /**
     * @var array<string, string>
     */
    public const STATUSES = [
        self::STATUS_OPEN => 'Open',
        self::STATUS_DONE => 'Done',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    /**
     * @var array<string, string>
     */
    public const TYPES = [
        self::TYPE_CALL => 'Call',
        self::TYPE_CALLBACK => 'Callback',
        self::TYPE_MEETING => 'Meeting',
        self::TYPE_QUOTE => 'Quote',
        self::TYPE_FOLLOWUP => 'Follow-up',
        self::TYPE_OTHER => 'Other',
    ];

    /**
     * @var array<string, string>
     */
    public const PRIORITIES = [
        self::PRIORITY_LOW => 'Low',
        self::PRIORITY_NORMAL => 'Normal',
        self::PRIORITY_HIGH => 'High',
    ];

    protected $fillable = [
        'subject_type',
        'subject_id',
        'contact_id',
        'title',
        'description',
        'type',
        'status',
        'priority',
        'due_at',
        'assigned_to',
        'created_by',
        'completed_at',
        'completed_by',
        'result',
    ];

    /**
     * @var array<string, class-string>
     */
    protected $allowedFilters = [
        'id' => Where::class,
        'status' => Where::class,
        'type' => Where::class,
        'assigned_to' => Where::class,
        'contact_id' => Where::class,
    ];

    protected $allowedSorts = [
        'id',
        'due_at',
        'created_at',
        'status',
        'priority',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * @param  Builder<CrmTask>  $query
     * @return Builder<CrmTask>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * @param  Builder<CrmTask>  $query
     * @return Builder<CrmTask>
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->open()
            ->whereNotNull('due_at')
            ->where('due_at', '<', now());
    }

    /**
     * @param  Builder<CrmTask>  $query
     * @return Builder<CrmTask>
     */
    public function scopeAssignedTo(Builder $query, ?User $user): Builder
    {
        if ($user === null) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where('assigned_to', $user->id);
    }

    /**
     * @param  Builder<CrmTask>  $query
     * @return Builder<CrmTask>
     */
    public function scopeDueWithin(Builder $query, \DateTimeInterface $from, \DateTimeInterface $to): Builder
    {
        return $query->whereNotNull('due_at')
            ->whereBetween('due_at', [$from, $to]);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? (string) $this->type;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }

    public function priorityLabel(): string
    {
        return self::PRIORITIES[$this->priority] ?? (string) $this->priority;
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [self::STATUS_DONE, self::STATUS_CANCELLED], true);
    }

    public function isOverdue(): bool
    {
        return $this->isOpen()
            && $this->due_at !== null
            && $this->due_at->lt(now());
    }

    public function subjectLabel(): string
    {
        $subject = $this->subject;
        if ($subject instanceof Lead) {
            return 'Lead #'.$subject->id.' '.trim((string) $subject->full_name);
        }
        if ($subject instanceof PhoneClick) {
            return 'Phone click #'.$subject->id;
        }
        if ($subject instanceof Contact) {
            return 'Contact #'.$subject->id.' '.trim((string) $subject->full_name);
        }

        return $this->contact
            ? 'Contact #'.$this->contact->id
            : '—';
    }
}

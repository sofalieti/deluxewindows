<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmTaskEvent extends Model
{
    public const UPDATED_AT = null;

    public const ACTION_COMPLETED = 'completed';

    public const ACTION_CANCELLED = 'cancelled';

    public const ACTION_REOPENED = 'reopened';

    public const ACTION_AUTO_COMPLETED = 'auto_completed';

    /**
     * @var array<string, string>
     */
    public const ACTIONS = [
        self::ACTION_COMPLETED => 'Completed',
        self::ACTION_CANCELLED => 'Cancelled',
        self::ACTION_REOPENED => 'Reopened',
        self::ACTION_AUTO_COMPLETED => 'Auto-completed',
    ];

    protected $fillable = [
        'crm_task_id',
        'user_id',
        'action',
        'comment',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(CrmTask::class, 'crm_task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actionLabel(): string
    {
        return self::ACTIONS[$this->action] ?? (string) $this->action;
    }
}

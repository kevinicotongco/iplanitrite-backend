<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventChecklistAssigneeTypeEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventChecklistAssignee extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'event_checklist_assignees';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'assignee_type' => EventChecklistAssigneeTypeEnum::class,
    ];

    public function eventChecklist(): BelongsTo
    {
        return $this->belongsTo(EventChecklist::class, 'event_checklist_id');
    }

    /**
     * Get the assignee (polymorphic-like based on assignee_type)
     * Note: Not using true polymorphic relations since we need to support both Staff and Client
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assignee_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'assignee_id');
    }
}

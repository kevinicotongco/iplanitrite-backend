<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventChecklistStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventChecklist extends Model
{
    use HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'status' => EventChecklistStatusEnum::class,
        'due_date' => 'datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(EventChecklistGroup::class, 'event_checklist_group_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function assignees(): HasMany
    {
        return $this->hasMany(EventChecklistAssignee::class, 'event_checklist_id');
    }
}

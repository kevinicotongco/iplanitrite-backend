<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AuditActionEnum;
use App\Enums\AuditTypeEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventChecklistGroupLog extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'event_checklist_group_logs';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'audit_type' => AuditTypeEnum::class,
        'action' => AuditActionEnum::class,
        'audit_date' => 'datetime',
    ];

    public function eventChecklistGroup(): BelongsTo
    {
        return $this->belongsTo(EventChecklistGroup::class, 'event_checklist_group_id');
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventSeat extends Model
{
    use HasUuids;

    protected $table = 'event_seats';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $guarded = [];

    public function floorPlan(): BelongsTo
    {
        return $this->belongsTo(EventFloorPlan::class, 'event_floor_plan_id');
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(EventGuest::class, 'event_guest_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'updated_by');
    }
}

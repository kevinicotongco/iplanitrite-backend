<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventGuestStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventGuest extends Model
{
    use HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'status' => EventGuestStatusEnum::class,
        'sort_order' => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(EventGuestGroup::class, 'event_guest_group_id');
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

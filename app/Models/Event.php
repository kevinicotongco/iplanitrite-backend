<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'supplier_id',
        'name',
        'description',
        'status',
        'event_date',
        'celebrant_one_id',
        'celebrant_two_id',
        'address_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => EventStatusEnum::class,
        'event_date' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function celebrantOne(): BelongsTo
    {
        return $this->belongsTo(Celebrant::class, 'celebrant_one_id');
    }

    public function celebrantTwo(): BelongsTo
    {
        return $this->belongsTo(Celebrant::class, 'celebrant_two_id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'event_clients')
            ->withPivot('id', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at')
            ->withTimestamps();
    }

    public function checklistGroups(): HasMany
    {
        return $this->hasMany(EventChecklistGroup::class);
    }

    public function guestGroups(): HasMany
    {
        return $this->hasMany(EventGuestGroup::class);
    }
}

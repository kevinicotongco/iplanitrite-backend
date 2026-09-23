<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventStatusEnum;
use App\Enums\EventTypeEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'status' => EventStatusEnum::class,
        'event_type' => EventTypeEnum::class,
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function celebrantOne(): BelongsTo
    {
        return $this->belongsTo(Celebrant::class, 'celebrant_one_id');
    }

    public function celebrantTwo(): BelongsTo
    {
        return $this->belongsTo(Celebrant::class, 'celebrant_two_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'updated_by');
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

    public function segments(): HasMany
    {
        return $this->hasMany(EventSegment::class);
    }

    public function primarySegments(): HasMany
    {
        return $this->hasMany(EventSegment::class)->where('is_primary', '=', \DB::raw('true'));
    }
}

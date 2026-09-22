<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Celebrant extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = [];

    public function profilePictureDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'profile_picture');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function contactNumber(): BelongsTo
    {
        return $this->belongsTo(ContactNumber::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    public function eventsAsCelebrantOne(): HasMany
    {
        return $this->hasMany(Event::class, 'celebrant_one_id');
    }

    public function eventsAsCelebrantTwo(): HasMany
    {
        return $this->hasMany(Event::class, 'celebrant_two_id');
    }
}

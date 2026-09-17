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

    protected $fillable = [
        'event_guest_group_id',
        'first_name',
        'middle_name',
        'last_name',
        'profile_picture',
        'address_id',
        'contact_number_id',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => EventGuestStatusEnum::class,
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(EventGuestGroup::class, 'event_guest_group_id');
    }

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
}

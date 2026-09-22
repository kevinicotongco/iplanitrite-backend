<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class SupplierStaff extends Authenticatable
{
    use HasApiTokens, HasUuids, Notifiable, SoftDeletes;

    protected $table = 'supplier_staff';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = [];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password_must_change' => 'boolean',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(SupplierRole::class, 'supplier_role_id');
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

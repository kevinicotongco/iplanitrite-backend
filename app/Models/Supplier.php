<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SupplierStatusEnum;
use App\Enums\SupplierSubscriptionTierEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'status',
        'logo',
        'description',
        'address_id',
        'country_id',
        'contact_number_id',
        'subscription_tier',
        'timezone',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => SupplierStatusEnum::class,
        'subscription_tier' => SupplierSubscriptionTierEnum::class,
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function contactNumber(): BelongsTo
    {
        return $this->belongsTo(ContactNumber::class);
    }

    public function logoDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'logo');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(SupplierStaff::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(SupplierRole::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function templateChecklistGroups(): HasMany
    {
        return $this->hasMany(SupplierTemplateChecklistGroup::class);
    }
}

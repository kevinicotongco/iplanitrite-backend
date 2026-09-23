<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountStatusEnum;
use App\Enums\AccountSubscriptionTierEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'accounts';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'status' => AccountStatusEnum::class,
        'subscription_tier' => AccountSubscriptionTierEnum::class,
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
        return $this->hasMany(Staff::class, 'account_id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(AccountRole::class, 'account_id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'account_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'account_id');
    }

    public function templateChecklistGroups(): HasMany
    {
        return $this->hasMany(AccountTemplateChecklistGroup::class, 'account_id');
    }
}

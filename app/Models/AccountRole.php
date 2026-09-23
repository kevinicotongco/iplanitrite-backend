<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountRole extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'account_roles';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = [];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'updated_by');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(AccountRolePermission::class, 'account_role_id');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class, 'account_role_id');
    }
}

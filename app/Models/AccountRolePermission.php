<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountRolePermissionEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountRolePermission extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'account_role_permissions';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'permission' => AccountRolePermissionEnum::class,
        ];
    }

    public function accountRole(): BelongsTo
    {
        return $this->belongsTo(AccountRole::class, 'account_role_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'updated_by');
    }
}

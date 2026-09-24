<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AuditActionEnum;
use App\Enums\AuditTypeEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierLog extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'supplier_logs';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'audit_type' => AuditTypeEnum::class,
        'action' => AuditActionEnum::class,
        'audit_date' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}

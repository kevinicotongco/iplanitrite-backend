<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ChecklistFrequencyTypeEnum;
use App\Enums\FrequencyAnchorEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierTemplateChecklist extends Model
{
    use HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'frequency_type' => ChecklistFrequencyTypeEnum::class,
        'frequency_anchor' => FrequencyAnchorEnum::class,
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(SupplierTemplateChecklistGroup::class, 'supplier_template_checklist_group_id');
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

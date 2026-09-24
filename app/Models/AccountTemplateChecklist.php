<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ChecklistFrequencyTypeEnum;
use App\Enums\FrequencyAnchorEnum;
use App\Enums\ResponsibilityTypeEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountTemplateChecklist extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'account_template_checklists';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'frequency_type' => ChecklistFrequencyTypeEnum::class,
        'frequency_anchor' => FrequencyAnchorEnum::class,
        'responsibility_type' => ResponsibilityTypeEnum::class,
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(AccountTemplateChecklistGroup::class, 'account_template_checklist_group_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'updated_by');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}

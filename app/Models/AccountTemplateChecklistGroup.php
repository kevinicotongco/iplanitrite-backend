<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventTypeEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountTemplateChecklistGroup extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'account_template_checklist_groups';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'event_type' => EventTypeEnum::class,
    ];

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

    public function checklists(): HasMany
    {
        return $this->hasMany(AccountTemplateChecklist::class, 'account_template_checklist_group_id');
    }
}

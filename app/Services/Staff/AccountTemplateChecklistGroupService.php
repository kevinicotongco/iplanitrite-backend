<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\AccountTemplateChecklistGroupWithChecklistsData;
use App\Enums\EventTypeEnum;
use App\Models\AccountTemplateChecklistGroup;
use Illuminate\Support\Collection;

readonly class AccountTemplateChecklistGroupService
{
    public function __construct(
        private AccountTemplateChecklistGroup $model,
    ) {}

    /**
     * @param string $accountId
     * @param EventTypeEnum $eventType
     * @return Collection<AccountTemplateChecklistGroupWithChecklistsData>
     */
    public function getTemplateGroupsWithChecklists(string $accountId, EventTypeEnum $eventType): Collection
    {
        $templateGroups = $this->model::where('account_id', $accountId)
            ->where('event_type', $eventType->value)
            ->with('checklists')
            ->get();

        return $templateGroups->map(function ($group) {
            return AccountTemplateChecklistGroupWithChecklistsData::fromModel($group);
        });
    }
}

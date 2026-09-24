<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\AccountTemplateChecklistGroupWithChecklistsData;
use App\Enums\ChecklistGroupTypeEnum;
use App\Enums\EventTypeEnum;
use App\Models\AccountTemplateChecklistGroup;
use App\Models\Staff;
use Illuminate\Support\Collection;

readonly class AccountTemplateChecklistGroupService
{
    public function __construct(
        private AccountTemplateChecklistGroup $groupModel,
    ) {}

    /**
     * Get the authenticated staff user
     */
    private function getAuthenticatedUser(): Staff
    {
        $user = auth()->user();
        if (!$user instanceof Staff) {
            throw new \Exception('Authenticated user is not a Staff member');
        }
        return $user;
    }

    /**
     * Get template groups with checklists (existing method for compatibility)
     *
     * @param string $accountId
     * @param EventTypeEnum $eventType
     * @return Collection<AccountTemplateChecklistGroupWithChecklistsData>
     */
    public function getTemplateGroupsWithChecklists(string $accountId, EventTypeEnum $eventType): Collection
    {
        $templateGroups = $this->groupModel::where('account_id', $accountId)
            ->where('event_type', $eventType->value)
            ->orderBy('sort_order')
            ->with('checklists')
            ->get();

        return $templateGroups->map(function ($group) {
            return AccountTemplateChecklistGroupWithChecklistsData::fromModel($group);
        });
    }

    /**
     * Get all checklist groups filtered by type and event type
     *
     * @param ChecklistGroupTypeEnum $checklistType
     * @param EventTypeEnum $eventType
     * @return Collection<int, AccountTemplateChecklistGroupWithChecklistsData>
     */
    public function getGroupsByTypeAndEventType(
        ChecklistGroupTypeEnum $checklistType,
        EventTypeEnum $eventType
    ): Collection {
        $authenticatedUser = $this->getAuthenticatedUser();
        $groups = $this->groupModel::where('account_id', $authenticatedUser->account_id)
            ->where('checklist_type', $checklistType->value)
            ->where('event_type', $eventType->value)
            ->orderBy('sort_order')
            ->get();

        return $groups->map(fn(AccountTemplateChecklistGroup $group) =>
            AccountTemplateChecklistGroupWithChecklistsData::fromModel($group)
        );
    }

    /**
     * Create a new checklist group
     *
     * @param string $name
     * @param ChecklistGroupTypeEnum $checklistType
     * @param EventTypeEnum $eventType
     * @return void
     */
    public function createGroup(
        string $name,
        ChecklistGroupTypeEnum $checklistType,
        EventTypeEnum $eventType
    ): void {
        $authenticatedUser = $this->getAuthenticatedUser();

        // Get the max sort_order for this type and event type
        $maxSortOrder = $this->groupModel::where('account_id', $authenticatedUser->account_id)
            ->where('checklist_type', $checklistType->value)
            ->where('event_type', $eventType->value)
            ->max('sort_order');

        $sortOrder = $maxSortOrder !== null ? $maxSortOrder + 1 : 1;

        $this->groupModel::create([
            'account_id' => $authenticatedUser->account_id,
            'name' => $name,
            'event_type' => $eventType->value,
            'checklist_type' => $checklistType->value,
            'sort_order' => $sortOrder,
            'created_by' => $authenticatedUser->id,
            'updated_by' => $authenticatedUser->id,
        ]);
    }

    /**
     * Update a checklist group's name
     *
     * @param string $groupId
     * @param string $name
     * @return void
     */
    public function updateGroupName(string $groupId, string $name): void
    {
        $authenticatedUser = $this->getAuthenticatedUser();

        $group = $this->groupModel::where('id', $groupId)
            ->where('account_id', $authenticatedUser->account_id)
            ->firstOrFail();

        $group->update([
            'name' => $name,
            'updated_by' => $authenticatedUser->id,
        ]);
    }

    /**
     * Bulk update sort order for groups
     *
     * @param array<array{id: string, sortOrder: int}> $sortData
     * @return void
     */
    public function updateGroupsSortOrder(array $sortData): void
    {
        $authenticatedUser = $this->getAuthenticatedUser();

        foreach ($sortData as $item) {
            $group = $this->groupModel::where('id', $item['id'])
                ->where('account_id', $authenticatedUser->account_id)
                ->firstOrFail();

            $group->update([
                'sort_order' => $item['sortOrder'],
                'updated_by' => $authenticatedUser->id,
            ]);
        }
    }

    /**
     * Delete a checklist group (soft delete)
     *
     * @param string $groupId
     * @return void
     */
    public function deleteGroup(string $groupId): void
    {
        $authenticatedUser = $this->getAuthenticatedUser();

        $group = $this->groupModel::where('id', $groupId)
            ->where('account_id', $authenticatedUser->account_id)
            ->firstOrFail();

        $group->delete();
    }
}

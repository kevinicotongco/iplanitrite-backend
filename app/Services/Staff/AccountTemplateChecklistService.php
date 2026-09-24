<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Enums\ChecklistFrequencyTypeEnum;
use App\Enums\ChecklistGroupTypeEnum;
use App\Enums\FrequencyAnchorEnum;
use App\Enums\ResponsibilityTypeEnum;
use App\Models\AccountTemplateChecklist;
use App\Models\AccountTemplateChecklistGroup;
use App\Models\Staff;

readonly class AccountTemplateChecklistService
{
    public function __construct(
        private AccountTemplateChecklist $checklistModel,
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
     * Create a new checklist under a group
     *
     * @param string $groupId
     * @param string $name
     * @return void
     */
    public function createChecklist(string $groupId, string $name): void
    {
        $group = $this->groupModel::where('id', $groupId)
            ->where('account_id', $this->getAuthenticatedUser()->account_id)
            ->firstOrFail();

        // Get the max sort_order for checklists in this group
        $maxSortOrder = $this->checklistModel::where('account_template_checklist_group_id', $groupId)
            ->max('sort_order');

        $sortOrder = $maxSortOrder !== null ? $maxSortOrder + 1 : 1;

        $this->checklistModel::create([
            'account_template_checklist_group_id' => $groupId,
            'name' => $name,
            'sort_order' => $sortOrder,
            'created_by' => $this->getAuthenticatedUser()->id,
            'updated_by' => $this->getAuthenticatedUser()->id,
        ]);
    }

    /**
     * Update checklist name
     *
     * @param string $groupId
     * @param string $checklistId
     * @param string $name
     * @return void
     */
    public function updateChecklistName(string $groupId, string $checklistId, string $name): void
    {
        $checklist = $this->getChecklistForUpdate($groupId, $checklistId);

        $checklist->update([
            'name' => $name,
            'updated_by' => $this->getAuthenticatedUser()->id,
        ]);
    }

    /**
     * Update checklist frequency
     *
     * @param string $groupId
     * @param string $checklistId
     * @param ChecklistFrequencyTypeEnum $frequencyType
     * @param FrequencyAnchorEnum $frequencyAnchor
     * @param int $frequencyValue
     * @return void
     */
    public function updateChecklistFrequency(
        string $groupId,
        string $checklistId,
        ChecklistFrequencyTypeEnum $frequencyType,
        FrequencyAnchorEnum $frequencyAnchor,
        int $frequencyValue
    ): void {
        $checklist = $this->getChecklistForUpdate($groupId, $checklistId);

        $checklist->update([
            'frequency_type' => $frequencyType->value,
            'frequency_anchor' => $frequencyAnchor->value,
            'frequency_value' => $frequencyValue,
            'updated_by' => $this->getAuthenticatedUser()->id,
        ]);
    }

    /**
     * Update checklist responsibility type
     *
     * @param string $groupId
     * @param string $checklistId
     * @param ResponsibilityTypeEnum $responsibilityType
     * @return void
     */
    public function updateChecklistResponsibility(
        string $groupId,
        string $checklistId,
        ResponsibilityTypeEnum $responsibilityType
    ): void {
        $checklist = $this->getChecklistForUpdate($groupId, $checklistId);

        $checklist->update([
            'responsibility_type' => $responsibilityType->value,
            'updated_by' => $this->getAuthenticatedUser()->id,
        ]);
    }

    /**
     * Update checklist supplier
     *
     * @param string $groupId
     * @param string $checklistId
     * @param string $supplierId
     * @return void
     * @throws \Exception
     */
    public function updateChecklistSupplier(
        string $groupId,
        string $checklistId,
        string $supplierId
    ): void {
        $checklist = $this->getChecklistForUpdate($groupId, $checklistId);

        // Validate that the group is a Supplier type
        $group = $this->groupModel::findOrFail($groupId);
        if ($group->checklist_type !== ChecklistGroupTypeEnum::Supplier) {
            throw new \Exception('Supplier can only be assigned to checklists in Supplier checklist groups');
        }

        // Validate that the supplier belongs to the same account
        $supplier = \App\Models\Supplier::findOrFail($supplierId);
        if ($supplier->account_id !== $this->getAuthenticatedUser()->account_id) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'supplierId' => ['The selected supplier does not belong to your account.']
            ]);
        }

        $checklist->update([
            'supplier_id' => $supplierId,
            'updated_by' => $this->getAuthenticatedUser()->id,
        ]);
    }

    /**
     * Bulk update sort order for checklists
     *
     * @param string $groupId
     * @param array<array{id: string, sortOrder: int}> $sortData
     * @return void
     */
    public function updateChecklistsSortOrder(string $groupId, array $sortData): void
    {
        // Verify group belongs to authenticated user's account
        $this->groupModel::where('id', $groupId)
            ->where('account_id', $this->getAuthenticatedUser()->account_id)
            ->firstOrFail();

        foreach ($sortData as $item) {
            $checklist = $this->checklistModel::where('id', $item['id'])
                ->where('account_template_checklist_group_id', $groupId)
                ->firstOrFail();

            $checklist->update([
                'sort_order' => $item['sortOrder'],
                'updated_by' => $this->getAuthenticatedUser()->id,
            ]);
        }
    }

    /**
     * Delete a checklist (soft delete)
     *
     * @param string $groupId
     * @param string $checklistId
     * @return void
     */
    public function deleteChecklist(string $groupId, string $checklistId): void
    {
        $checklist = $this->getChecklistForUpdate($groupId, $checklistId);
        $checklist->delete();
    }

    /**
     * Helper method to get a checklist for update operations
     *
     * @param string $groupId
     * @param string $checklistId
     * @return AccountTemplateChecklist
     */
    private function getChecklistForUpdate(string $groupId, string $checklistId): AccountTemplateChecklist
    {
        // Verify group belongs to authenticated user's account
        $this->groupModel::where('id', $groupId)
            ->where('account_id', $this->getAuthenticatedUser()->account_id)
            ->firstOrFail();

        // Get the checklist
        return $this->checklistModel::where('id', $checklistId)
            ->where('account_template_checklist_group_id', $groupId)
            ->firstOrFail();
    }
}

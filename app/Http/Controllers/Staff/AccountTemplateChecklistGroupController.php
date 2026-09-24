<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Dto\Request\SortRequestDto;
use App\Dto\Response\AccountTemplateChecklistGroupResponseDto;
use App\Enums\ChecklistGroupTypeEnum;
use App\Enums\EventTypeEnum;
use App\Http\Requests\CreateAccountTemplateChecklistGroupRequest;
use App\Http\Requests\UpdateAccountTemplateChecklistGroupNameRequest;
use App\Services\Staff\AccountTemplateChecklistGroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class AccountTemplateChecklistGroupController extends Controller
{
    public function __construct(
        private readonly AccountTemplateChecklistGroupService $groupService
    ) {}

    /**
     * Get checklist groups by type and event type
     */
    public function index(string $type, string $eventType): JsonResponse
    {
        $checklistType = ChecklistGroupTypeEnum::from($type);
        $eventTypeEnum = EventTypeEnum::from($eventType);

        $groups = $this->groupService->getGroupsByTypeAndEventType($checklistType, $eventTypeEnum);

        $responseDtos = $groups->map(fn($groupData) =>
            AccountTemplateChecklistGroupResponseDto::fromData($groupData)
        );

        return response()->json($responseDtos->map(fn($dto) => $dto->toArray())->toArray(), 200);
    }

    /**
     * Create checklist group
     */
    public function store(string $type, string $eventType, CreateAccountTemplateChecklistGroupRequest $request): JsonResponse
    {
        $checklistType = ChecklistGroupTypeEnum::from($type);
        $eventTypeEnum = EventTypeEnum::from($eventType);
        $dto = $request->toDto();

        DB::transaction(function () use ($dto, $checklistType, $eventTypeEnum) {
            $this->groupService->createGroup($dto->name, $checklistType, $eventTypeEnum);
        });

        return response()->json([], 200);
    }

    /**
     * Update checklist group name
     */
    public function update(string $groupId, UpdateAccountTemplateChecklistGroupNameRequest $request): JsonResponse
    {
        $dto = $request->toDto();

        DB::transaction(function () use ($groupId, $dto) {
            $this->groupService->updateGroupName($groupId, $dto->name);
        });

        return response()->json([], 200);
    }

    /**
     * Bulk update group sort order
     */
    public function updateSort(Request $request): JsonResponse
    {
        $sortData = array_map(
            fn($item) => SortRequestDto::fromArray($item),
            $request->input()
        );

        $sortDataArray = array_map(
            fn(SortRequestDto $dto) => ['id' => $dto->id, 'sortOrder' => $dto->sortOrder],
            $sortData
        );

        DB::transaction(function () use ($sortDataArray) {
            $this->groupService->updateGroupsSortOrder($sortDataArray);
        });

        return response()->json([], 200);
    }

    /**
     * Delete checklist group
     */
    public function destroy(string $groupId): JsonResponse
    {
        DB::transaction(function () use ($groupId) {
            $this->groupService->deleteGroup($groupId);
        });

        return response()->json([], 200);
    }
}

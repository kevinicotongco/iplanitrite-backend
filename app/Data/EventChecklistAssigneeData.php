<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\EventChecklistAssigneeTypeEnum;
use App\Models\Client;
use App\Models\EventChecklistAssignee;
use App\Models\Staff;

final readonly class EventChecklistAssigneeData
{
    public function __construct(
        public string $assigneeId,
        public EventChecklistAssigneeTypeEnum $assigneeType,
        public string $assigneeName,
        public ?string $assigneeProfilePicture,
    ) {}

    public static function fromModel(EventChecklistAssignee $assignee): ?self
    {
        $user = match ($assignee->assignee_type) {
            EventChecklistAssigneeTypeEnum::Staff => $assignee->staff,
            EventChecklistAssigneeTypeEnum::Client => $assignee->client,
        };

        if (!$user instanceof Staff && !$user instanceof Client) {
            return null;
        }

        return new self(
            assigneeId: $assignee->assignee_id,
            assigneeType: $assignee->assignee_type,
            assigneeName: $user->first_name . ' ' . $user->last_name,
            assigneeProfilePicture: $user->profile_picture,
        );
    }
}

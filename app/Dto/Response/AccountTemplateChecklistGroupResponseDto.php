<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Models\AccountTemplateChecklistGroup;

readonly class AccountTemplateChecklistGroupResponseDto
{
    public function __construct(
        public string $id,
        public string $accountId,
        public string $name,
        public string $eventType,
    ) {}

    public static function fromModel(AccountTemplateChecklistGroup $accountTemplateChecklistGroup): self
    {
        return new self(
            id: $accountTemplateChecklistGroup->id,
            accountId: $accountTemplateChecklistGroup->account_id,
            name: $accountTemplateChecklistGroup->name,
            eventType: $accountTemplateChecklistGroup->event_type->value,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'accountId' => $this->accountId,
            'name' => $this->name,
            'eventType' => $this->eventType,
        ];
    }
}

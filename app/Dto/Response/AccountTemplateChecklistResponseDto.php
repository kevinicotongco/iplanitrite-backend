<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Models\AccountTemplateChecklist;

readonly class AccountTemplateChecklistResponseDto
{
    public function __construct(
        public string  $id,
        public string  $accountTemplateChecklistGroupId,
        public string  $name,
        public ?string $description,
    ) {}

    public static function fromModel(AccountTemplateChecklist $accountTemplateChecklist): self
    {
        return new self(
            id: $accountTemplateChecklist->id,
            accountTemplateChecklistGroupId: $accountTemplateChecklist->account_template_checklist_group_id,
            name: $accountTemplateChecklist->name,
            description: $accountTemplateChecklist->description,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'accountTemplateChecklistGroupId' => $this->accountTemplateChecklistGroupId,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Models\SupplierTemplateChecklist;

readonly class SupplierTemplateChecklistResponseDto
{
    public function __construct(
        public string $id,
        public string $supplierTemplateChecklistGroupId,
        public string $name,
        public ?string $description,
    ) {}

    public static function fromModel(SupplierTemplateChecklist $supplierTemplateChecklist): self
    {
        return new self(
            id: $supplierTemplateChecklist->id,
            supplierTemplateChecklistGroupId: $supplierTemplateChecklist->supplier_template_checklist_group_id,
            name: $supplierTemplateChecklist->name,
            description: $supplierTemplateChecklist->description,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'supplierTemplateChecklistGroupId' => $this->supplierTemplateChecklistGroupId,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}

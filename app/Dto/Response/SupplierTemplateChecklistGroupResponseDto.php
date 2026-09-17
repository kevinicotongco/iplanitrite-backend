<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Models\SupplierTemplateChecklistGroup;

readonly class SupplierTemplateChecklistGroupResponseDto
{
    public function __construct(
        public string $id,
        public string $supplierId,
        public string $name,
    ) {}

    public static function fromModel(SupplierTemplateChecklistGroup $supplierTemplateChecklistGroup): self
    {
        return new self(
            id: $supplierTemplateChecklistGroup->id,
            supplierId: $supplierTemplateChecklistGroup->supplier_id,
            name: $supplierTemplateChecklistGroup->name,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'supplierId' => $this->supplierId,
            'name' => $this->name,
        ];
    }
}

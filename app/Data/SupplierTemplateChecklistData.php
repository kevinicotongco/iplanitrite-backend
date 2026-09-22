<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\SupplierTemplateChecklist;

final readonly class SupplierTemplateChecklistData
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public int $frequencyDays,
    ) {}

    public static function fromModel(SupplierTemplateChecklist $checklist): self
    {
        return new self(
            id: $checklist->id,
            name: $checklist->name,
            description: $checklist->description,
            frequencyDays: $checklist->frequency_days,
        );
    }
}

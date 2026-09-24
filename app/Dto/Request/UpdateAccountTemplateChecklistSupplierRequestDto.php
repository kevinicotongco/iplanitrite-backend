<?php

declare(strict_types=1);

namespace App\Dto\Request;

readonly class UpdateAccountTemplateChecklistSupplierRequestDto
{
    public function __construct(
        public string $supplierId,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            supplierId: $data['supplierId'],
        );
    }
}

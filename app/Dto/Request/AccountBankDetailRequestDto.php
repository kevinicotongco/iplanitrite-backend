<?php

declare(strict_types=1);

namespace App\Dto\Request;

readonly class AccountBankDetailRequestDto
{
    public function __construct(
        public string $bankName,
        public string $accountNumber,
        public ?string $qrCode,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            bankName: $data['bankName'],
            accountNumber: $data['accountNumber'],
            qrCode: $data['qrCode'] ?? null,
        );
    }
}

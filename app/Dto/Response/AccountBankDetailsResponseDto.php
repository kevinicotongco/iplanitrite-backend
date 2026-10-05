<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Models\AccountBankDetail;

readonly class AccountBankDetailsResponseDto
{
    public function __construct(
        public string $id,
        public string $bankName,
        public string $accountNumber,
        public ?string $qrCode,
    ) {}

    public static function fromModel(AccountBankDetail $bankDetail): self
    {
        return new self(
            id: $bankDetail->id,
            bankName: $bankDetail->bank_name,
            accountNumber: $bankDetail->account_number,
            qrCode: $bankDetail->qr_code,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'bankName' => $this->bankName,
            'accountNumber' => $this->accountNumber,
            'qrCode' => $this->qrCode,
        ];
    }
}

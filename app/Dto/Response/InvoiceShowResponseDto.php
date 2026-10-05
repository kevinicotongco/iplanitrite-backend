<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Data\InvoiceShowData;
use App\Models\AccountBankDetail;

readonly class InvoiceShowResponseDto
{
    /**
     * @param array<int, AccountBankDetailsResponseDto> $bankDetails
     */
    public function __construct(
        public InvoiceResponseDto $invoice,
        public array $bankDetails,
    ) {}

    public static function fromData(InvoiceShowData $data): self
    {
        return new self(
            invoice: InvoiceResponseDto::fromData($data->invoice),
            bankDetails: $data->bankDetails
                ->map(fn(AccountBankDetail $bankDetail): AccountBankDetailsResponseDto => AccountBankDetailsResponseDto::fromModel($bankDetail))
                ->values()
                ->all(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'invoice' => $this->invoice->toArray(),
            'bankDetails' => array_map(
                fn(AccountBankDetailsResponseDto $dto): array => $dto->toArray(),
                $this->bankDetails
            ),
        ];
    }
}

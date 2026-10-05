<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Data\InvoiceWithBalanceData;
use App\Enums\InvoiceStatusEnum;

readonly class InvoiceResponseDto
{
    public function __construct(
        public string $id,
        public string $invoiceNumber,
        public InvoiceStatusEnum $status,
        public ?string $cancellationNotes,
        public string $dueDate,
        public string $balanceDue,
    ) {}

    public static function fromData(InvoiceWithBalanceData $data): self
    {
        return new self(
            id: $data->invoice->id,
            invoiceNumber: $data->invoice->invoice_number,
            status: $data->invoice->status,
            cancellationNotes: $data->invoice->cancellation_notes,
            dueDate: $data->invoice->due_date->format('Y-m-d'),
            balanceDue: $data->balanceDue,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'invoiceNumber' => $this->invoiceNumber,
            'status' => $this->status->value,
            'cancellationNotes' => $this->cancellationNotes,
            'dueDate' => $this->dueDate,
            'balanceDue' => $this->balanceDue,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Carbon\Carbon;

readonly class UpdateEventChecklistDueDateRequestDto
{
    public const DATE_FORMAT = 'Y-m-d';

    public function __construct(
        public ?Carbon $dueDate,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            dueDate: $data['dueDate'] !== null
                ? Carbon::createFromFormat(self::DATE_FORMAT, $data['dueDate'])->startOfDay()
                : null,
        );
    }
}

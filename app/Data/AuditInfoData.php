<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\AuditTypeEnum;

readonly class AuditInfoData
{
    public function __construct(
        public string $auditBy,
        public AuditTypeEnum $auditType,
    ) {}
}

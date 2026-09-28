<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Collection;

final readonly class EventChecklistGroupsByTypeData
{
    /**
     * @param Collection<int, EventChecklistGroupWithChecklistsData> $supplier
     * @param Collection<int, EventChecklistGroupWithChecklistsData> $general
     */
    public function __construct(
        public Collection $supplier,
        public Collection $general,
    ) {}
}

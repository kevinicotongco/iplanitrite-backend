<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\EventGuest;
use App\Models\EventGuestGroup;
use Illuminate\Support\Collection;

final readonly class EventGuestGroupWithGuestsData
{
    /**
     * @param Collection<int, EventGuest> $guests
     */
    public function __construct(
        public EventGuestGroup $group,
        public Collection $guests,
    ) {}
}

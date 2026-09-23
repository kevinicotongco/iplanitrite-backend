<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Admin;
use App\Models\Client;
use App\Models\Staff;

final readonly class LoginResult
{
    public function __construct(
        public string $token,
        public Admin|Staff|Client $user,
    ) {}
}

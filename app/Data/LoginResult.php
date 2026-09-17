<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Admin;
use App\Models\Client;
use App\Models\SupplierStaff;

final readonly class LoginResult
{
    public function __construct(
        public string $token,
        public Admin|SupplierStaff|Client $user,
    ) {}
}

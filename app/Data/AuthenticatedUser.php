<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Admin;
use App\Models\Client;
use App\Models\Staff;
use Illuminate\Contracts\Auth\Authenticatable;

class AuthenticatedUser
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
    ) {}

    /**
     * Create an AuthenticatedUser instance from the current authenticated user
     *
     * @return self
     * @throws \Exception
     */
    public static function fromAuth(): self
    {
        $user = auth()->user();

        if (!$user instanceof Authenticatable) {
            throw new \Exception('No authenticated user found');
        }

        return match (true) {
            $user instanceof Admin => new self(
                id: $user->id,
                name: $user->first_name . ' ' . $user->last_name,
                email: $user->email,
            ),
            $user instanceof Staff => new self(
                id: $user->id,
                name: $user->first_name . ' ' . $user->last_name,
                email: $user->email,
            ),
            $user instanceof Client => new self(
                id: $user->id,
                name: $user->first_name . ' ' . $user->last_name,
                email: $user->email,
            ),
            default => throw new \Exception('Authenticated user type is not recognized'),
        };
    }
}

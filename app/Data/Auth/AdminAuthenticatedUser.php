<?php

declare(strict_types=1);

namespace App\Data\Auth;

use App\Models\Admin;

class AdminAuthenticatedUser extends AuthenticatedUser
{
    public static function fromAuth(): self
    {
        $user = auth()->user();

        if (!$user instanceof Admin) {
            throw new \Exception('Authenticated user is not an Admin');
        }

        return new self(
            id: $user->id,
            name: $user->first_name . ' ' . $user->last_name,
            email: $user->email,
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Client;

class ClientAuthenticatedUser extends AuthenticatedUser
{
    public static function fromAuth(): self
    {
        $user = auth()->user();

        if (!$user instanceof Client) {
            throw new \Exception('Authenticated user is not a Client');
        }

        return new self(
            id: $user->id,
            name: $user->first_name . ' ' . $user->last_name,
            email: $user->email,
        );
    }
}

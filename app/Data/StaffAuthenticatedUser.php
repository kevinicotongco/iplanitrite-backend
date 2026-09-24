<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Staff;

class StaffAuthenticatedUser extends AuthenticatedUser
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $accountId,
    ) {
        parent::__construct(
            id: $id,
            name: $name,
            email: $email,
        );
    }

    public static function fromAuth(): self
    {
        $user = auth()->user();

        if (!$user instanceof Staff) {
            throw new \Exception('Authenticated user is not a Staff member');
        }

        return new self(
            id: $user->id,
            name: $user->first_name . ' ' . $user->last_name,
            email: $user->email,
            accountId: $user->account_id,
        );
    }
}

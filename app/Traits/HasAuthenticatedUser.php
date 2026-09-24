<?php

declare(strict_types=1);

namespace App\Traits;

use App\Data\AuthenticatedUser;

trait HasAuthenticatedUser
{
    /**
     * Get the authenticated user
     *
     * @return AuthenticatedUser
     */
    protected function getAuthenticatedUser(): AuthenticatedUser
    {
        return AuthenticatedUser::fromAuth();
    }
}

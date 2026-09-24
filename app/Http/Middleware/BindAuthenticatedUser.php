<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Data\Auth\AdminAuthenticatedUser;
use App\Data\Auth\ClientAuthenticatedUser;
use App\Data\Auth\StaffAuthenticatedUser;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Staff;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BindAuthenticatedUser
{
    /**
     * @throws Exception
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user instanceof Staff) {
            app()->instance(StaffAuthenticatedUser::class, StaffAuthenticatedUser::fromAuth());
        } elseif ($user instanceof Client) {
            app()->instance(ClientAuthenticatedUser::class, ClientAuthenticatedUser::fromAuth());
        } elseif ($user instanceof Admin) {
            app()->instance(AdminAuthenticatedUser::class, AdminAuthenticatedUser::fromAuth());
        }

        return $next($request);
    }
}

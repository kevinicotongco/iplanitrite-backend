<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Data\AdminAuthenticatedUser;
use App\Data\ClientAuthenticatedUser;
use App\Data\StaffAuthenticatedUser;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Staff;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BindAuthenticatedUser
{
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

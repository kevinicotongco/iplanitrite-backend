<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\AccountRolePermissionEnum;
use App\Services\Staff\AccountRoleService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireStaffPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $requiredPermission = AccountRolePermissionEnum::from($permission);

        if (!app(AccountRoleService::class)->staffHasPermission($requiredPermission)) {
            return new JsonResponse(['message' => 'You do not have permission to perform this action.'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Dto\Request\ChangePasswordRequestDto;
use App\Dto\Request\StaffLoginRequestDto;
use App\Dto\Request\StaffUpdateProfileRequestDto;
use App\DTO\Response\StaffLoginResponseDto;
use App\DTO\Response\StaffResponseDto;
use App\Services\Staff\StaffUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class StaffUserController extends Controller
{
    public function login(StaffLoginRequestDto $request, StaffUserService $service): JsonResponse
    {
        $result = $service->login($request->email, $request->password);

        if ($result === null) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $responseDto = StaffLoginResponseDto::fromLoginResult($result);

        return response()->json($responseDto->toArray(), 200);
    }

    public function profile(Request $request, StaffUserService $service): JsonResponse
    {
        $staffId = auth('staff')->id();

        $staff = $service->getProfile($staffId);

        $responseDto = StaffResponseDto::fromModel($staff);

        return response()->json($responseDto->toArray(), 200);
    }

    public function updateProfile(StaffUpdateProfileRequestDto $request, StaffUserService $service): JsonResponse
    {
        $staffId = auth('staff')->id();

        $staff = DB::transaction(function () use ($staffId, $request, $service) {
            return $service->updateProfile($staffId, $request->toArray());
        });

        $responseDto = StaffResponseDto::fromModel($staff);

        return response()->json($responseDto->toArray(), 200);
    }

    public function changePassword(ChangePasswordRequestDto $request, StaffUserService $service): JsonResponse
    {
        $staffId = auth('staff')->id();

        DB::transaction(function () use ($staffId, $request, $service) {
            $service->changePassword(
                $staffId,
                $request->currentPassword,
                $request->newPassword
            );
        });

        return response()->json([], 204);
    }
}

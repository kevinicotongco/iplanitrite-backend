<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Dto\Request\AdminLoginRequestDto;
use App\Dto\Request\AdminUpdateProfileRequestDto;
use App\Dto\Request\ChangePasswordRequestDto;
use App\DTO\Response\AdminLoginResponseDto;
use App\DTO\Response\AdminResponseDto;
use App\Services\Admin\AdminUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Info(
 *     title="iPlanItRite API",
 *     version="1.0.0",
 *     description="API documentation for iPlanItRite event planning platform",
 *     @OA\Contact(
 *         email="support@iplanitrite.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Local development server"
 * )
 *
 * @OA\Server(
 *     url="https://api.iplanitrite.com/api",
 *     description="Production server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter JWT token obtained from login endpoint"
 * )
 *
 * @OA\Schema(
 *     schema="AdminLoginRequest",
 *     type="object",
 *     required={"email", "password"},
 *     @OA\Property(property="email", type="string", format="email", example="admin@iplanitrite.com"),
 *     @OA\Property(property="password", type="string", format="password", example="password123")
 * )
 *
 * @OA\Schema(
 *     schema="AdminLoginResponse",
 *     type="object",
 *     @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGc..."),
 *     @OA\Property(
 *         property="user",
 *         ref="#/components/schemas/AdminResponse"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="AdminResponse",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="9d5e8b9a-1234-5678-9abc-def012345678"),
 *     @OA\Property(property="email", type="string", format="email", example="admin@iplanitrite.com"),
 *     @OA\Property(property="first_name", type="string", example="John"),
 *     @OA\Property(property="last_name", type="string", example="Doe"),
 *     @OA\Property(property="avatar", type="string", nullable=true, example="9d5e8b9a-avatar-uuid"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T12:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T12:00:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="AdminUpdateProfileRequest",
 *     type="object",
 *     @OA\Property(property="avatar", type="string", nullable=true, example="9d5e8b9a-avatar-uuid", description="Document UUID for avatar"),
 *     @OA\Property(property="first_name", type="string", example="John"),
 *     @OA\Property(property="last_name", type="string", example="Doe")
 * )
 *
 * @OA\Schema(
 *     schema="ChangePasswordRequest",
 *     type="object",
 *     required={"current_password", "new_password", "new_password_confirmation"},
 *     @OA\Property(property="current_password", type="string", format="password", example="oldPassword123"),
 *     @OA\Property(property="new_password", type="string", format="password", example="newPassword123"),
 *     @OA\Property(property="new_password_confirmation", type="string", format="password", example="newPassword123")
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Invalid credentials")
 * )
 */
class AdminUserController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(
        private AdminUserService $service
    ) {
    }

    /**
     * Admin login
     *
     * @OA\Post(
     *     path="/admin/login",
     *     tags={"Admin Authentication"},
     *     summary="Admin user login",
     *     description="Authenticate admin user and return JWT token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/AdminLoginRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful login",
     *         @OA\JsonContent(ref="#/components/schemas/AdminLoginResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The email field is required."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="The email field is required."))
     *             )
     *         )
     *     )
     * )
     */
    public function login(AdminLoginRequestDto $request): JsonResponse
    {
        $result = $this->service->login($request->email, $request->password);

        if ($result === null) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $responseDto = AdminLoginResponseDto::fromLoginResult($result);

        return response()->json($responseDto->toArray(), 200);
    }

    /**
     * Get admin profile
     *
     * @OA\Get(
     *     path="/admin/profile",
     *     tags={"Admin Profile"},
     *     summary="Get authenticated admin profile",
     *     description="Retrieve the profile information of the currently authenticated admin user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of admin profile",
     *         @OA\JsonContent(ref="#/components/schemas/AdminResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function profile(Request $request): JsonResponse
    {
        $adminId = auth('admin')->id();

        $admin = $this->service->getProfile($adminId);

        $responseDto = AdminResponseDto::fromModel($admin);

        return response()->json($responseDto->toArray(), 200);
    }

    /**
     * Update admin profile
     *
     * @OA\Put(
     *     path="/admin/profile",
     *     tags={"Admin Profile"},
     *     summary="Update admin profile",
     *     description="Update the profile information of the currently authenticated admin user",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/AdminUpdateProfileRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/AdminResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object"
     *             )
     *         )
     *     )
     * )
     * @throws \Throwable
     */
    public function updateProfile(AdminUpdateProfileRequestDto $request): JsonResponse
    {
        $adminId = auth('admin')->id();

        $admin = DB::transaction(function () use ($adminId, $request) {
            return $this->service->updateProfile($adminId, $request->toArray());
        });

        $responseDto = AdminResponseDto::fromModel($admin);

        return response()->json($responseDto->toArray(), 200);
    }

    /**
     * Change admin password
     *
     * @OA\Put(
     *     path="/admin/change-password",
     *     tags={"Admin Profile"},
     *     summary="Change admin password",
     *     description="Change the password of the currently authenticated admin user",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ChangePasswordRequest")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Password changed successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Current password is incorrect",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Current password is invalid")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object"
     *             )
     *         )
     *     )
     * )
     */
    public function changePassword(ChangePasswordRequestDto $request): JsonResponse
    {
        $adminId = auth('admin')->id();

        DB::transaction(function () use ($adminId, $request) {
            $this->service->changePassword(
                $adminId,
                $request->currentPassword,
                $request->newPassword
            );
        });

        return response()->json([], 204);
    }
}

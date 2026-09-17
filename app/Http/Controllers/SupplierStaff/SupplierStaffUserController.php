<?php

declare(strict_types=1);

namespace App\Http\Controllers\SupplierStaff;

use App\Dto\Request\ChangePasswordRequestDto;
use App\Dto\Request\SupplierStaffLoginRequestDto;
use App\Dto\Request\SupplierStaffUpdateProfileRequestDto;
use App\DTO\Response\SupplierStaffLoginResponseDto;
use App\DTO\Response\SupplierStaffResponseDto;
use App\Services\SupplierStaff\SupplierStaffUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Schema(
 *     schema="SupplierStaffLoginRequest",
 *     type="object",
 *     required={"email", "password"},
 *     @OA\Property(property="email", type="string", format="email", example="staff@supplier.com"),
 *     @OA\Property(property="password", type="string", format="password", example="password123")
 * )
 *
 * @OA\Schema(
 *     schema="SupplierStaffLoginResponse",
 *     type="object",
 *     @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGc..."),
 *     @OA\Property(
 *         property="user",
 *         ref="#/components/schemas/SupplierStaffResponse"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="SupplierStaffResponse",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="9d5e8b9a-1234-5678-9abc-def012345678"),
 *     @OA\Property(property="supplier_id", type="string", format="uuid", example="9d5e8b9a-supplier-uuid"),
 *     @OA\Property(property="supplier_role_id", type="string", format="uuid", example="9d5e8b9a-role-uuid"),
 *     @OA\Property(property="email", type="string", format="email", example="staff@supplier.com"),
 *     @OA\Property(property="first_name", type="string", example="Jane"),
 *     @OA\Property(property="middle_name", type="string", nullable=true, example="Marie"),
 *     @OA\Property(property="last_name", type="string", example="Smith"),
 *     @OA\Property(property="profile_picture", type="string", nullable=true, example="9d5e8b9a-picture-uuid"),
 *     @OA\Property(property="address_id", type="string", format="uuid", nullable=true, example="9d5e8b9a-address-uuid"),
 *     @OA\Property(property="contact_number_id", type="string", format="uuid", nullable=true, example="9d5e8b9a-contact-uuid"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T12:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T12:00:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="SupplierStaffUpdateProfileRequest",
 *     type="object",
 *     @OA\Property(property="avatar", type="string", nullable=true, example="9d5e8b9a-avatar-uuid", description="Document UUID for profile picture"),
 *     @OA\Property(property="first_name", type="string", example="Jane"),
 *     @OA\Property(property="last_name", type="string", example="Smith")
 * )
 */
class SupplierStaffUserController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(
        private readonly SupplierStaffUserService $service
    ) {
    }

    /**
     * Supplier staff login
     *
     * @OA\Post(
     *     path="/supplier-staff/login",
     *     tags={"Supplier Staff Authentication"},
     *     summary="Supplier staff user login",
     *     description="Authenticate supplier staff user and return JWT token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/SupplierStaffLoginRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful login",
     *         @OA\JsonContent(ref="#/components/schemas/SupplierStaffLoginResponse")
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
    public function login(SupplierStaffLoginRequestDto $request): JsonResponse
    {
        $result = $this->service->login($request->email, $request->password);

        if ($result === null) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $responseDto = SupplierStaffLoginResponseDto::fromLoginResult($result);

        return response()->json($responseDto->toArray(), 200);
    }

    /**
     * Get supplier staff profile
     *
     * @OA\Get(
     *     path="/supplier-staff/profile",
     *     tags={"Supplier Staff Profile"},
     *     summary="Get authenticated supplier staff profile",
     *     description="Retrieve the profile information of the currently authenticated supplier staff user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of supplier staff profile",
     *         @OA\JsonContent(ref="#/components/schemas/SupplierStaffResponse")
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
        $staffId = auth('supplier_staff')->id();

        $staff = $this->service->getProfile($staffId);

        $responseDto = SupplierStaffResponseDto::fromModel($staff);

        return response()->json($responseDto->toArray(), 200);
    }

    /**
     * Update supplier staff profile
     *
     * @OA\Put(
     *     path="/supplier-staff/profile",
     *     tags={"Supplier Staff Profile"},
     *     summary="Update supplier staff profile",
     *     description="Update the profile information of the currently authenticated supplier staff user",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/SupplierStaffUpdateProfileRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SupplierStaffResponse")
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
    public function updateProfile(SupplierStaffUpdateProfileRequestDto $request): JsonResponse
    {
        $staffId = auth('supplier_staff')->id();

        $staff = DB::transaction(function () use ($staffId, $request) {
            return $this->service->updateProfile($staffId, $request->toArray());
        });

        $responseDto = SupplierStaffResponseDto::fromModel($staff);

        return response()->json($responseDto->toArray(), 200);
    }

    /**
     * Change supplier staff password
     *
     * @OA\Put(
     *     path="/supplier-staff/change-password",
     *     tags={"Supplier Staff Profile"},
     *     summary="Change supplier staff password",
     *     description="Change the password of the currently authenticated supplier staff user",
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
        $staffId = auth('supplier_staff')->id();

        DB::transaction(function () use ($staffId, $request) {
            $this->service->changePassword(
                $staffId,
                $request->currentPassword,
                $request->newPassword
            );
        });

        return response()->json([], 204);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Dto\Request\ChangePasswordRequestDto;
use App\Dto\Request\ClientLoginRequestDto;
use App\Dto\Request\ClientUpdateProfileRequestDto;
use App\DTO\Response\ClientLoginResponseDto;
use App\DTO\Response\ClientResponseDto;
use App\Services\Client\ClientUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * @OA\Schema(
 *     schema="ClientLoginRequest",
 *     type="object",
 *     required={"email", "password"},
 *     @OA\Property(property="email", type="string", format="email", example="client@example.com"),
 *     @OA\Property(property="password", type="string", format="password", example="password123")
 * )
 *
 * @OA\Schema(
 *     schema="ClientLoginResponse",
 *     type="object",
 *     @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGc..."),
 *     @OA\Property(
 *         property="user",
 *         ref="#/components/schemas/ClientResponse"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ClientResponse",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="9d5e8b9a-1234-5678-9abc-def012345678"),
 *     @OA\Property(property="supplier_id", type="string", format="uuid", example="9d5e8b9a-supplier-uuid"),
 *     @OA\Property(property="email", type="string", format="email", example="client@example.com"),
 *     @OA\Property(property="first_name", type="string", example="Alice"),
 *     @OA\Property(property="middle_name", type="string", nullable=true, example="Marie"),
 *     @OA\Property(property="last_name", type="string", example="Johnson"),
 *     @OA\Property(property="profile_picture", type="string", nullable=true, example="9d5e8b9a-picture-uuid"),
 *     @OA\Property(property="address_id", type="string", format="uuid", nullable=true, example="9d5e8b9a-address-uuid"),
 *     @OA\Property(property="contact_number_id", type="string", format="uuid", nullable=true, example="9d5e8b9a-contact-uuid"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T12:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T12:00:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="ClientUpdateProfileRequest",
 *     type="object",
 *     @OA\Property(property="avatar", type="string", nullable=true, example="9d5e8b9a-avatar-uuid", description="Document UUID for profile picture"),
 *     @OA\Property(property="first_name", type="string", example="Alice"),
 *     @OA\Property(property="last_name", type="string", example="Johnson")
 * )
 */
class ClientUserController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(
        private readonly ClientUserService $service
    ) {
    }

    /**
     * Client login
     *
     * @OA\Post(
     *     path="/client/login",
     *     tags={"Client Authentication"},
     *     summary="Client user login",
     *     description="Authenticate client user and return JWT token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ClientLoginRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful login",
     *         @OA\JsonContent(ref="#/components/schemas/ClientLoginResponse")
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
    public function login(ClientLoginRequestDto $request): JsonResponse
    {
        $result = $this->service->login($request->email, $request->password);

        if ($result === null) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $responseDto = ClientLoginResponseDto::fromLoginResult($result);

        return response()->json($responseDto->toArray(), 200);
    }

    /**
     * Get client profile
     *
     * @OA\Get(
     *     path="/client/profile",
     *     tags={"Client Profile"},
     *     summary="Get authenticated client profile",
     *     description="Retrieve the profile information of the currently authenticated client user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of client profile",
     *         @OA\JsonContent(ref="#/components/schemas/ClientResponse")
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
        $clientId = auth('client')->id();

        $client = $this->service->getProfile($clientId);

        $responseDto = ClientResponseDto::fromModel($client);

        return response()->json($responseDto->toArray(), 200);
    }

    /**
     * Update client profile
     *
     * @OA\Put(
     *     path="/client/profile",
     *     tags={"Client Profile"},
     *     summary="Update client profile",
     *     description="Update the profile information of the currently authenticated client user",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ClientUpdateProfileRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ClientResponse")
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
     * @throws Throwable
     */
    public function updateProfile(ClientUpdateProfileRequestDto $request): JsonResponse
    {
        $clientId = auth('client')->id();

        $client = DB::transaction(function () use ($clientId, $request) {
            return $this->service->updateProfile($clientId, $request->toArray());
        });

        $responseDto = ClientResponseDto::fromModel($client);

        return response()->json($responseDto->toArray(), 200);
    }

    /**
     * Change client password
     *
     * @OA\Put(
     *     path="/client/change-password",
     *     tags={"Client Profile"},
     *     summary="Change client password",
     *     description="Change the password of the currently authenticated client user",
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
        $clientId = auth('client')->id();

        DB::transaction(function () use ($clientId, $request) {
            $this->service->changePassword(
                $clientId,
                $request->currentPassword,
                $request->newPassword
            );
        });

        return response()->json([], 204);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Dto\Request\CreateAccountRequestDto;
use App\Dto\Request\GetAccountsRequestDto;
use App\Dto\Request\UpdateAccountRequestDto;
use App\Dto\Response\AccountResponseDto;
use App\Http\Requests\CreateAccountRequest;
use App\Http\Requests\GetAccountsRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Services\Admin\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function __construct(
        private readonly AccountService $accountService,
    ) {}

    public function index(GetAccountsRequest $request): JsonResponse
    {
        $requestDto = GetAccountsRequestDto::fromArray($request->validated());

        $accounts = $this->accountService->getAccounts($requestDto);

        $response = $accounts->map(fn($account) => AccountResponseDto::fromModel($account)->toArray());

        return response()->json($response, 200);
    }

    /**
     * @OA\Post(
     *     path="/admin/accounts",
     *     tags={"Admin Accounts"},
     *     summary="Create a new account",
     *     description="Create a new account with initial administrator staff member",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "subscriptionTier", "countryId", "timezone", "staff"},
     *                 @OA\Property(property="name", type="string", example="Acme Events"),
     *                 @OA\Property(property="logo", type="string", format="binary", description="Logo image file"),
     *                 @OA\Property(property="description", type="string", example="Premium event planning services"),
     *                 @OA\Property(property="subscriptionTier", type="string", enum={"Free", "Standard", "Premium"}, example="Premium"),
     *                 @OA\Property(property="countryId", type="string", format="uuid", example="9d5e8b9a-1234-5678-9abc-def012345678"),
     *                 @OA\Property(property="timezone", type="string", example="America/New_York"),
     *                 @OA\Property(
     *                     property="address",
     *                     type="object",
     *                     @OA\Property(property="line1", type="string", example="123 Main St"),
     *                     @OA\Property(property="line2", type="string", nullable=true, example="Suite 100"),
     *                     @OA\Property(property="city", type="string", example="New York"),
     *                     @OA\Property(property="state", type="string", example="NY"),
     *                     @OA\Property(property="zip", type="string", example="10001"),
     *                     @OA\Property(property="lat", type="string", nullable=true, example="40.7128"),
     *                     @OA\Property(property="long", type="string", nullable=true, example="-74.0060")
     *                 ),
     *                 @OA\Property(property="contactNumber", type="string", example="+1234567890"),
     *                 @OA\Property(
     *                     property="staff",
     *                     type="object",
     *                     required={"email", "firstName", "lastName"},
     *                     @OA\Property(property="email", type="string", format="email", example="admin@acmeevents.com"),
     *                     @OA\Property(property="firstName", type="string", example="John"),
     *                     @OA\Property(property="middleName", type="string", nullable=true, example="A"),
     *                     @OA\Property(property="lastName", type="string", example="Doe")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Account created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/AccountResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     * @throws \Throwable
     */
    public function store(CreateAccountRequest $request): JsonResponse
    {
        $requestDto = CreateAccountRequestDto::fromArray($request->validated());

        $account = DB::transaction(fn() => $this->accountService->createAccount($requestDto));

        $response = AccountResponseDto::fromModel($account);

        return response()->json($response->toArray(), 201);
    }

    /**
     * @OA\Put(
     *     path="/admin/accounts/{id}",
     *     tags={"Admin Accounts"},
     *     summary="Update an existing account",
     *     description="Update account details including logo, address, and contact information",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid"),
     *         description="Account UUID"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "subscriptionTier", "countryId", "timezone"},
     *                 @OA\Property(property="name", type="string", example="Acme Events"),
     *                 @OA\Property(property="logo", type="string", format="binary", description="Logo image file"),
     *                 @OA\Property(property="description", type="string", example="Premium event planning services"),
     *                 @OA\Property(property="subscriptionTier", type="string", enum={"Free", "Standard", "Premium"}, example="Premium"),
     *                 @OA\Property(property="countryId", type="string", format="uuid", example="9d5e8b9a-1234-5678-9abc-def012345678"),
     *                 @OA\Property(property="timezone", type="string", example="America/New_York"),
     *                 @OA\Property(
     *                     property="address",
     *                     type="object",
     *                     @OA\Property(property="line1", type="string", example="123 Main St"),
     *                     @OA\Property(property="line2", type="string", nullable=true, example="Suite 100"),
     *                     @OA\Property(property="city", type="string", example="New York"),
     *                     @OA\Property(property="state", type="string", example="NY"),
     *                     @OA\Property(property="zip", type="string", example="10001"),
     *                     @OA\Property(property="lat", type="string", nullable=true, example="40.7128"),
     *                     @OA\Property(property="long", type="string", nullable=true, example="-74.0060")
     *                 ),
     *                 @OA\Property(property="contactNumber", type="string", example="+1234567890")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Account updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Account")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Account not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     * @throws \Throwable
     */
    public function update(UpdateAccountRequest $request, string $id): JsonResponse
    {
        $requestDto = UpdateAccountRequestDto::fromArray($request->validated());

        $adminId = auth('admin')->id();

        $account = DB::transaction(fn() => $this->accountService->updateAccount($id, $requestDto));

        $response = AccountResponseDto::fromModel($account);

        return response()->json($response->toArray(), 200);
    }
}

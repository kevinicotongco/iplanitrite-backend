<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Dto\Request\CreateSupplierRequestDto;
use App\Dto\Request\GetSuppliersRequestDto;
use App\Dto\Request\UpdateSupplierRequestDto;
use App\Dto\Response\SupplierResponseDto;
use App\Http\Requests\CreateSupplierRequest;
use App\Http\Requests\GetSuppliersRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Services\Admin\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    public function __construct(
        private readonly SupplierService $supplierService,
    ) {}

    /**
     * @OA\Get(
     *     path="/admin/suppliers",
     *     tags={"Admin Suppliers"},
     *     summary="Get list of suppliers",
     *     description="Retrieve a filtered and sorted list of suppliers",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="searchText",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         description="Search suppliers by name"
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", enum={"Active", "Disabled", "FailedPayment"}),
     *         description="Filter by supplier status"
     *     ),
     *     @OA\Parameter(
     *         name="tier",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", enum={"Free", "Standard", "Premium"}),
     *         description="Filter by subscription tier"
     *     ),
     *     @OA\Parameter(
     *         name="countryId",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="uuid"),
     *         description="Filter by country UUID"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of suppliers",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/SupplierResponse")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function index(GetSuppliersRequest $request): JsonResponse
    {
        $requestDto = GetSuppliersRequestDto::fromArray($request->validated());

        $suppliers = $this->supplierService->getSuppliers($requestDto);

        $response = $suppliers->map(fn($supplier) => SupplierResponseDto::fromModel($supplier)->toArray());

        return response()->json($response, 200);
    }

    /**
     * @OA\Post(
     *     path="/admin/suppliers",
     *     tags={"Admin Suppliers"},
     *     summary="Create a new supplier",
     *     description="Create a new supplier with initial administrator staff member",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "subscriptionTier", "countryId", "timezone", "supplierStaff"},
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
     *                     property="supplierStaff",
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
     *         description="Supplier created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SupplierResponse")
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
    public function store(CreateSupplierRequest $request): JsonResponse
    {
        $requestDto = CreateSupplierRequestDto::fromArray($request->validated());

        $supplier = DB::transaction(fn() => $this->supplierService->createSupplier($requestDto));

        $response = SupplierResponseDto::fromModel($supplier);

        return response()->json($response->toArray(), 201);
    }

    /**
     * @OA\Put(
     *     path="/admin/suppliers/{id}",
     *     tags={"Admin Suppliers"},
     *     summary="Update an existing supplier",
     *     description="Update supplier details including logo, address, and contact information",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid"),
     *         description="Supplier UUID"
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
     *         description="Supplier updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SupplierResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Supplier not found",
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
    public function update(UpdateSupplierRequest $request, string $id): JsonResponse
    {
        $requestDto = UpdateSupplierRequestDto::fromArray($request->validated());

        $adminId = auth('admin')->id();

        $supplier = DB::transaction(fn() => $this->supplierService->updateSupplier($id, $requestDto));

        $response = SupplierResponseDto::fromModel($supplier);

        return response()->json($response->toArray(), 200);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\SupplierStaff;

use App\Dto\Request\CreateEventRequestDto;
use App\Dto\Request\GetEventsRequestDto;
use App\Dto\Request\UpdateEventRequestDto;
use App\Dto\Response\EventResponseDto;
use App\Http\Requests\CreateEventRequest;
use App\Http\Requests\GetEventsRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Services\SupplierStaff\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Schema(
 *     schema="GetEventsRequest",
 *     type="object",
 *     @OA\Property(property="searchText", type="string", nullable=true, example="Wedding", description="Filter events by event name"),
 *     @OA\Property(property="status", type="string", enum={"Pending", "Ongoing", "Completed", "Cancelled"}, nullable=true, example="Pending", description="Filter events by status")
 * )
 *
 * @OA\Schema(
 *     schema="CelebrantRequest",
 *     type="object",
 *     required={"firstName", "lastName"},
 *     @OA\Property(property="firstName", type="string", example="John"),
 *     @OA\Property(property="middleName", type="string", nullable=true, example="Michael"),
 *     @OA\Property(property="lastName", type="string", example="Doe"),
 *     @OA\Property(property="address", ref="#/components/schemas/AddressRequest", nullable=true),
 *     @OA\Property(property="contactNumber", type="string", nullable=true, example="+1234567890")
 * )
 *
 * @OA\Schema(
 *     schema="AddressRequest",
 *     type="object",
 *     required={"line1", "city", "state", "zip"},
 *     @OA\Property(property="line1", type="string", example="123 Main Street"),
 *     @OA\Property(property="line2", type="string", nullable=true, example="Apt 4B"),
 *     @OA\Property(property="city", type="string", example="New York"),
 *     @OA\Property(property="state", type="string", example="NY"),
 *     @OA\Property(property="zip", type="string", example="10001"),
 *     @OA\Property(property="lat", type="string", nullable=true, example="40.712776"),
 *     @OA\Property(property="long", type="string", nullable=true, example="-74.005974")
 * )
 *
 * @OA\Schema(
 *     schema="ClientCreateRequest",
 *     type="object",
 *     required={"email", "firstName", "lastName"},
 *     @OA\Property(property="email", type="string", format="email", example="client@example.com"),
 *     @OA\Property(property="firstName", type="string", example="Jane"),
 *     @OA\Property(property="lastName", type="string", example="Smith")
 * )
 *
 * @OA\Schema(
 *     schema="CreateEventRequest",
 *     type="object",
 *     required={"name", "status", "eventDate", "celebrantOne", "address", "clients"},
 *     @OA\Property(property="name", type="string", example="John and Jane's Wedding"),
 *     @OA\Property(property="description", type="string", nullable=true, example="A beautiful summer wedding"),
 *     @OA\Property(property="status", type="string", enum={"Pending", "Ongoing", "Completed", "Cancelled"}, example="Pending"),
 *     @OA\Property(property="eventDate", type="string", format="date-time", example="2024-06-15T14:00:00Z"),
 *     @OA\Property(property="celebrantOne", ref="#/components/schemas/CelebrantRequest"),
 *     @OA\Property(property="celebrantTwo", ref="#/components/schemas/CelebrantRequest", nullable=true),
 *     @OA\Property(property="address", ref="#/components/schemas/AddressRequest"),
 *     @OA\Property(
 *         property="clients",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/ClientCreateRequest")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="UpdateEventRequest",
 *     type="object",
 *     required={"name", "status", "eventDate", "celebrantOne", "address"},
 *     @OA\Property(property="name", type="string", example="John and Jane's Wedding"),
 *     @OA\Property(property="description", type="string", nullable=true, example="A beautiful summer wedding"),
 *     @OA\Property(property="status", type="string", enum={"Pending", "Ongoing", "Completed", "Cancelled"}, example="Pending"),
 *     @OA\Property(property="eventDate", type="string", format="date-time", example="2024-06-15T14:00:00Z"),
 *     @OA\Property(property="celebrantOne", ref="#/components/schemas/CelebrantRequest"),
 *     @OA\Property(property="celebrantTwo", ref="#/components/schemas/CelebrantRequest", nullable=true),
 *     @OA\Property(property="address", ref="#/components/schemas/AddressRequest")
 * )
 *
 * @OA\Schema(
 *     schema="EventResponse",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="9d5e8b9a-1234-5678-9abc-def012345678"),
 *     @OA\Property(property="supplierId", type="string", format="uuid", example="9d5e8b9a-supplier-uuid"),
 *     @OA\Property(property="name", type="string", example="John and Jane's Wedding"),
 *     @OA\Property(property="description", type="string", nullable=true, example="A beautiful summer wedding"),
 *     @OA\Property(property="status", type="string", example="Pending"),
 *     @OA\Property(property="eventDate", type="string", format="date-time", example="2024-06-15T14:00:00+00:00"),
 *     @OA\Property(property="celebrantOne", ref="#/components/schemas/CelebrantResponse", nullable=true),
 *     @OA\Property(property="celebrantTwo", ref="#/components/schemas/CelebrantResponse", nullable=true),
 *     @OA\Property(property="address", ref="#/components/schemas/AddressResponse", nullable=true)
 * )
 */
class EventController extends Controller
{
    public function __construct(
        private readonly EventService $service
    ) {}

    /**
     * Get filtered events
     *
     * @OA\Get(
     *     path="/api/suppliers/events",
     *     tags={"Supplier Events"},
     *     summary="Get filtered events for supplier",
     *     description="Retrieve a list of events with optional filtering by name and status",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="searchText",
     *         in="query",
     *         description="Filter events by event name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter events by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"Pending", "Ongoing", "Completed", "Cancelled"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of events",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/EventResponse")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function index(GetEventsRequest $request): JsonResponse
    {
        $staff = auth('supplier_staff')->user();
        $supplierId = $staff->supplier_id;

        $dto = GetEventsRequestDto::fromArray($request->validated());

        $events = $this->service->getEvents($supplierId, $dto);

        $response = $events->map(fn($event) => EventResponseDto::fromModel($event)->toArray());

        return response()->json($response, 200);
    }

    /**
     * Create a new event
     *
     * @OA\Post(
     *     path="/api/suppliers/events",
     *     tags={"Supplier Events"},
     *     summary="Create a new event",
     *     description="Create a new event with celebrants and clients. Sends welcome notification to newly created clients.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CreateEventRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Event created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/EventResponse")
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
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(CreateEventRequest $request): JsonResponse
    {
        $staff = auth('supplier_staff')->user();
        $supplierId = $staff->supplier_id;
        $supplier = $staff->supplier;
        $countryId = $supplier->country_id;
        $createdBy = $staff->id;

        $dto = CreateEventRequestDto::fromArray($request->validated());

        $event = DB::transaction(function () use ($supplierId, $countryId, $dto, $createdBy) {
            return $this->service->createEvent($supplierId, $countryId, $dto, $createdBy);
        });

        $responseDto = EventResponseDto::fromModel($event);

        return response()->json($responseDto->toArray(), 201);
    }

    /**
     * Update an existing event
     *
     * @OA\Put(
     *     path="/api/suppliers/events/{id}",
     *     tags={"Supplier Events"},
     *     summary="Update an existing event",
     *     description="Update event details including celebrants and address",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Event UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateEventRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Event updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/EventResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Event not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function update(UpdateEventRequest $request, string $id): JsonResponse
    {
        $staff = auth('supplier_staff')->user();
        $supplierId = $staff->supplier_id;
        $supplier = $staff->supplier;
        $countryId = $supplier->country_id;
        $updatedBy = $staff->id;

        $dto = UpdateEventRequestDto::fromArray($request->validated());

        $event = DB::transaction(function () use ($id, $supplierId, $countryId, $dto, $updatedBy) {
            return $this->service->updateEvent($id, $supplierId, $countryId, $dto, $updatedBy);
        });

        $responseDto = EventResponseDto::fromModel($event);

        return response()->json($responseDto->toArray(), 200);
    }
}

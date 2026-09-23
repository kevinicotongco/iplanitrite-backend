<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Dto\Request\CreateEventRequestDto;
use App\Dto\Request\GetEventsRequestDto;
use App\Dto\Request\UpdateEventRequestDto;
use App\Dto\Response\EventResponseDto;
use App\Http\Requests\CreateEventRequest;
use App\Http\Requests\GetEventsRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Services\Staff\AccountTemplateChecklistGroupService;
use App\Services\Staff\AddressService;
use App\Services\Staff\CelebrantService;
use App\Services\Staff\ClientService;
use App\Services\Staff\ContactNumberService;
use App\Services\Staff\EventChecklistGroupService;
use App\Services\Staff\EventChecklistService;
use App\Services\Staff\EventClientService;
use App\Services\Staff\EventSegmentService;
use App\Services\Staff\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    public function __construct() {}

    public function index(GetEventsRequest $request): JsonResponse
    {
        $staff = auth('staff')->user();
        $accountId = $staff->account_id;

        $dto = GetEventsRequestDto::fromArray($request->validated());

        // Create service instance with authenticated user
        $service = $this->createEventService($staff);

        $events = $service->getEvents($accountId, $dto);

        $response = $events->map(fn($eventData) => EventResponseDto::fromEventData($eventData)->toArray());

        return response()->json($response, 200);
    }

    public function store(CreateEventRequest $request): JsonResponse
    {
        $staff = auth('staff')->user();
        $accountId = $staff->account_id;
        $account = $staff->account;
        $countryId = $account->country_id;

        $dto = CreateEventRequestDto::fromArray($request->validated());

        // Create service instances with authenticated user
        $service = $this->createEventService($staff);

        DB::transaction(function () use ($service, $accountId, $countryId, $dto) {
            $service->createEvent($accountId, $countryId, $dto);
        });

        return response()->json(null, 201);
    }

    public function update(UpdateEventRequest $request, string $id): JsonResponse
    {
        $staff = auth('staff')->user();
        $accountId = $staff->account_id;
        $account = $staff->account;
        $countryId = $account->country_id;

        $dto = UpdateEventRequestDto::fromArray($request->validated());

        // Create service instances with authenticated user
        $service = $this->createEventService($staff);

        DB::transaction(function () use ($service, $id, $accountId, $countryId, $dto) {
            $service->updateEvent($id, $accountId, $countryId, $dto);
        });

        return response()->json(null, 200);
    }

    private function createEventService($staff): EventService
    {
        $contactNumberService = app(ContactNumberService::class, ['authenticatedUser' => $staff]);
        $addressService = app(AddressService::class, ['authenticatedUser' => $staff]);
        $celebrantService = app(CelebrantService::class, [
            'authenticatedUser' => $staff,
            'addressService' => $addressService,
            'contactNumberService' => $contactNumberService
        ]);
        $clientService = app(ClientService::class, ['authenticatedUser' => $staff]);
        $eventClientService = app(EventClientService::class, ['authenticatedUser' => $staff]);
        $eventChecklistService = app(EventChecklistService::class, ['authenticatedUser' => $staff]);
        $templateChecklistGroupService = app(AccountTemplateChecklistGroupService::class);
        $eventChecklistGroupService = app(EventChecklistGroupService::class, [
            'authenticatedUser' => $staff,
            'eventChecklistService' => $eventChecklistService,
            'templateChecklistGroupService' => $templateChecklistGroupService
        ]);
        $eventSegmentService = app(EventSegmentService::class, [
            'authenticatedUser' => $staff,
            'addressService' => $addressService
        ]);

        return app(EventService::class, [
            'authenticatedUser' => $staff,
            'celebrantService' => $celebrantService,
            'clientService' => $clientService,
            'eventClientService' => $eventClientService,
            'eventChecklistGroupService' => $eventChecklistGroupService,
            'eventSegmentService' => $eventSegmentService
        ]);
    }
}

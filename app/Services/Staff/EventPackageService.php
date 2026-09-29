<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\Auth\StaffAuthenticatedUser;
use App\Dto\Request\EventPackageRequestDto;
use App\Enums\EventTypeEnum;
use App\Models\EventPackage;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

readonly class EventPackageService
{
    private const EVENT_PACKAGE_ID_FIELD = 'eventPackageId';

    public function __construct(
        private StaffAuthenticatedUser $authenticatedUser,
        private EventPackage $eventPackageModel,
    ) {}

    /**
     * @return Collection<int, EventPackage>
     */
    public function getPackagesByEventType(EventTypeEnum $eventType): Collection
    {
        return $this->eventPackageModel::where('account_id', $this->authenticatedUser->accountId)
            ->where('event_type', $eventType)
            ->orderBy('name')
            ->get();
    }

    public function createPackage(EventTypeEnum $eventType, EventPackageRequestDto $dto): EventPackage
    {
        return $this->eventPackageModel::create([
            'account_id' => $this->authenticatedUser->accountId,
            'name' => $dto->name,
            'event_type' => $eventType,
            'price' => $dto->price,
            'description' => $dto->description,
            'created_by' => $this->authenticatedUser->id,
            'updated_by' => $this->authenticatedUser->id,
        ]);
    }

    public function updatePackage(EventTypeEnum $eventType, string $eventPackageId, EventPackageRequestDto $dto): EventPackage
    {
        $eventPackage = $this->getPackageForAccount($eventType, $eventPackageId);

        $eventPackage->update([
            'name' => $dto->name,
            'price' => $dto->price,
            'description' => $dto->description,
            'updated_by' => $this->authenticatedUser->id,
        ]);

        return $eventPackage;
    }

    public function deletePackage(EventTypeEnum $eventType, string $eventPackageId): void
    {
        $eventPackage = $this->getPackageForAccount($eventType, $eventPackageId);

        $eventPackage->update(['updated_by' => $this->authenticatedUser->id]);
        $eventPackage->delete();
    }

    /**
     * @throws ValidationException
     */
    public function getAssignablePackage(string $eventPackageId, EventTypeEnum $eventType, bool $allowDeleted = false): EventPackage
    {
        $query = $this->eventPackageModel::where('id', $eventPackageId)
            ->where('account_id', $this->authenticatedUser->accountId);

        if ($allowDeleted) {
            $query->withTrashed();
        }

        $eventPackage = $query->first();

        if ($eventPackage === null) {
            throw ValidationException::withMessages([
                self::EVENT_PACKAGE_ID_FIELD => 'The selected event package is invalid.',
            ]);
        }

        if ($eventPackage->event_type !== $eventType) {
            throw ValidationException::withMessages([
                self::EVENT_PACKAGE_ID_FIELD => 'The selected event package does not match the event type.',
            ]);
        }

        return $eventPackage;
    }

    private function getPackageForAccount(EventTypeEnum $eventType, string $eventPackageId): EventPackage
    {
        return $this->eventPackageModel::where('id', $eventPackageId)
            ->where('account_id', $this->authenticatedUser->accountId)
            ->where('event_type', $eventType)
            ->firstOrFail();
    }
}

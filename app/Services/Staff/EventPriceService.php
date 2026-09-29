<?php

declare(strict_types=1);

namespace App\Services\Staff;

use App\Data\Auth\StaffAuthenticatedUser;
use App\Dto\Request\EventPriceRequestDto;
use App\Dto\Request\SortRequestDto;
use App\Models\Event;
use App\Models\EventPackage;
use App\Models\EventPrice;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

readonly class EventPriceService
{
    private const INITIAL_SORT_ORDER = 0;
    private const INITIAL_COST_PRICE = 0;
    private const SUPPLIER_RELATIONS = ['supplier.contactNumber', 'supplier.address.country'];

    public function __construct(
        private StaffAuthenticatedUser $authenticatedUser,
        private EventPrice $eventPriceModel,
        private SupplierService $supplierService,
    ) {}

    /**
     * @return Collection<int, EventPrice>
     */
    public function getPricesForEvent(Event $event): Collection
    {
        return $this->eventPriceModel::where('account_id', $this->authenticatedUser->accountId)
            ->where('event_id', $event->id)
            ->with(self::SUPPLIER_RELATIONS)
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();
    }

    public function createInitialPrice(Event $event, EventPackage $eventPackage): EventPrice
    {
        return $this->eventPriceModel::create([
            'account_id' => $this->authenticatedUser->accountId,
            'event_id' => $event->id,
            'name' => $eventPackage->name,
            'cost_price' => self::INITIAL_COST_PRICE,
            'retail_price' => $eventPackage->price,
            'sort_order' => self::INITIAL_SORT_ORDER,
            'supplier_id' => null,
            'created_by' => $this->authenticatedUser->id,
            'updated_by' => $this->authenticatedUser->id,
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function createPrice(Event $event, EventPriceRequestDto $dto): EventPrice
    {
        $supplierId = $this->resolveSupplierId($dto->supplierId);

        $maxSortOrder = $this->eventPriceModel::where('account_id', $this->authenticatedUser->accountId)
            ->where('event_id', $event->id)
            ->max('sort_order');

        $eventPrice = $this->eventPriceModel::create([
            'account_id' => $this->authenticatedUser->accountId,
            'event_id' => $event->id,
            'name' => $dto->name,
            'cost_price' => $dto->costPrice,
            'retail_price' => $dto->retailPrice,
            'sort_order' => $maxSortOrder !== null ? $maxSortOrder + 1 : self::INITIAL_SORT_ORDER,
            'supplier_id' => $supplierId,
            'created_by' => $this->authenticatedUser->id,
            'updated_by' => $this->authenticatedUser->id,
        ]);

        return $eventPrice->load(self::SUPPLIER_RELATIONS);
    }

    /**
     * @throws ValidationException
     */
    public function updatePrice(Event $event, string $eventPriceId, EventPriceRequestDto $dto): EventPrice
    {
        $eventPrice = $this->getPriceForEvent($event, $eventPriceId);
        $supplierId = $this->resolveSupplierId($dto->supplierId);

        $eventPrice->update([
            'name' => $dto->name,
            'cost_price' => $dto->costPrice,
            'retail_price' => $dto->retailPrice,
            'supplier_id' => $supplierId,
            'updated_by' => $this->authenticatedUser->id,
        ]);

        return $eventPrice->load(self::SUPPLIER_RELATIONS);
    }

    /**
     * @param array<int, SortRequestDto> $sortDtos
     */
    public function sortPrices(Event $event, array $sortDtos): void
    {
        foreach ($sortDtos as $sortDto) {
            $eventPrice = $this->getPriceForEvent($event, $sortDto->id);

            $eventPrice->update([
                'sort_order' => $sortDto->sortOrder,
                'updated_by' => $this->authenticatedUser->id,
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    public function deletePrice(Event $event, string $eventPriceId): void
    {
        $eventPrice = $this->getPriceForEvent($event, $eventPriceId);
        $packagePrice = $this->getPackagePrice($event);

        if ($packagePrice !== null && $packagePrice->id === $eventPrice->id) {
            throw ValidationException::withMessages([
                'eventPriceId' => 'The event package price cannot be deleted.',
            ]);
        }

        $eventPrice->delete();
    }

    public function syncPackageRetailPrice(Event $event, EventPackage $eventPackage): void
    {
        $packagePrice = $this->getPackagePrice($event);

        $packagePrice?->update([
            'retail_price' => $eventPackage->price,
            'updated_by' => $this->authenticatedUser->id,
        ]);
    }

    private function getPackagePrice(Event $event): ?EventPrice
    {
        return $this->eventPriceModel::where('account_id', $this->authenticatedUser->accountId)
            ->where('event_id', $event->id)
            ->orderBy('created_at')
            ->first();
    }

    private function getPriceForEvent(Event $event, string $eventPriceId): EventPrice
    {
        return $this->eventPriceModel::where('id', $eventPriceId)
            ->where('account_id', $this->authenticatedUser->accountId)
            ->where('event_id', $event->id)
            ->firstOrFail();
    }

    /**
     * @throws ValidationException
     */
    private function resolveSupplierId(?string $supplierId): ?string
    {
        if ($supplierId === null) {
            return null;
        }

        $supplier = $this->supplierService->findSupplierWithRelationsById($supplierId);

        if ($supplier === null) {
            throw ValidationException::withMessages([
                'supplierId' => 'The selected supplier is invalid.',
            ]);
        }

        return $supplier->id;
    }
}

<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Models\Client;

readonly class ClientResponseDto
{
    public function __construct(
        public string                    $id,
        public string                    $accountId,
        public string                    $email,
        public string                    $firstName,
        public ?string                   $middleName,
        public string                    $lastName,
        public ?DocumentResponseDto      $profilePicture,
        public ?AddressResponseDto       $address,
        public ?ContactNumberResponseDto $contactNumber,
    ) {}

    public static function fromModel(Client $client): self
    {
        return new self(
            id: $client->id,
            accountId: $client->account_id,
            email: $client->email,
            firstName: $client->first_name,
            middleName: $client->middle_name,
            lastName: $client->last_name,
            profilePicture: $client->profilePictureDocument
                ? DocumentResponseDto::fromModel($client->profilePictureDocument)
                : null,
            address: $client->address
                ? AddressResponseDto::fromModel($client->address)
                : null,
            contactNumber: $client->contactNumber
                ? ContactNumberResponseDto::fromModel($client->contactNumber)
                : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'accountId' => $this->accountId,
            'email' => $this->email,
            'firstName' => $this->firstName,
            'middleName' => $this->middleName,
            'lastName' => $this->lastName,
            'profilePicture' => $this->profilePicture?->toArray(),
            'address' => $this->address?->toArray(),
            'contactNumber' => $this->contactNumber?->toArray(),
        ];
    }
}

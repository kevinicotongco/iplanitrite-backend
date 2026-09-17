<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Models\Admin;

readonly class AdminResponseDto
{
    public function __construct(
        public string $id,
        public ?string $avatar,
        public string $email,
        public string $firstName,
        public string $lastName,
    ) {}

    public static function fromModel(Admin $admin): self
    {
        return new self(
            id: $admin->id,
            avatar: $admin->avatar,
            email: $admin->email,
            firstName: $admin->first_name,
            lastName: $admin->last_name,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'avatar' => $this->avatar,
            'email' => $this->email,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
        ];
    }
}

<?php

namespace App\DTOs\Auth;

readonly class LoginDTO
{
    public function __construct(
        public ?string $email,
        public ?string $phone,
        public string $password,
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            email: $validated['email'] ?? null,
            phone: $validated['phone'] ?? null,
            password: $validated['password'],
        );
    }
}

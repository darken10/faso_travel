<?php

namespace App\DTOs\Auth;

use App\Enums\OtpChannelType;
use App\Enums\SexeUser;
use App\Enums\UserRole;

readonly class RegisterDTO
{
    public function __construct(
        public string $name,
        public ?string $email,
        public string $password,
        public ?string $first_name = null,
        public ?string $last_name = null,
        public ?SexeUser $sexe = null,
        public ?int $numero = null,
        public ?string $numero_identifiant = null,
        public ?UserRole $role = null,
        public ?int $compagnie_id = null,
        public ?OtpChannelType $verification_method = null,
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            name: $validated['name'],
            email: $validated['email'] ?? null,
            password: $validated['password'],
            first_name: $validated['first_name'] ?? null,
            last_name: $validated['last_name'] ?? null,
            sexe: isset($validated['sexe']) ? SexeUser::tryFrom($validated['sexe']) : null,
            numero: $validated['numero'] ?? null,
            numero_identifiant: $validated['numero_identifiant'] ?? null,
            role: isset($validated['role']) ? UserRole::tryFrom($validated['role']) : null,
            compagnie_id: $validated['compagnie_id'] ?? null,
            verification_method: isset($validated['verification_method'])
                ? OtpChannelType::tryFrom($validated['verification_method'])
                : null,
        );
    }
}

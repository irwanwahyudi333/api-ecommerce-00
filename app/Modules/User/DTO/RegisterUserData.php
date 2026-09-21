<?php

declare(strict_types=1);

namespace App\Modules\User\DTO;

/**
 * DTO khusus untuk proses REGISTRASI.
 * Dipisah dari UpdateUserData agar field yang wajib ada saat create
 * (name, email, password) tidak bisa nullable seperti pada update,
 * dan agar field sensitif (shop_id, permission) tidak bisa "menyusup"
 * lewat array request tanpa whitelist eksplisit.
 */
final readonly class RegisterUserData
{
    /**
     * @param  array<string, mixed>|null  $profile
     * @param  array<string, mixed>|null  $address
     */
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?array $profile = null,
        public ?array $address = null,
        public ?string $requestedPermission = null,
    ) {}

    /**
     * @param  array{name: string, email: string, password: string, profile?: array<string, mixed>|null, address?: array<string, mixed>|null}  $validated
     * @param  string|null  $requestedPermission  Diteruskan terpisah dari controller setelah
     *                                            melalui pengecekan otorisasi (anti privilege-escalation).
     */
    public static function fromValidated(array $validated, ?string $requestedPermission): self
    {
        return new self(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'],
            profile: $validated['profile'] ?? null,
            address: $validated['address'] ?? null,
            requestedPermission: $requestedPermission,
        );
    }
}

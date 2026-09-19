<?php

namespace App\Modules\Otp\DTO;

use InvalidArgumentException;

final class OtpResult
{
    private bool $valid;

    /** @var array<mixed> */
    private array $errors;

    private string $id;

    /**
     * @param  string|array<mixed>  $value
     */
    public function __construct(string|array $value)
    {
        if (is_string($value)) {
            $this->id = $value;
            $this->valid = true;
            $this->errors = []; // Initialize errors
        } elseif (is_array($value)) {
            $this->errors = $value;
            $this->valid = false;
            $this->id = ''; // Initialize id
        } else {
            throw new InvalidArgumentException('Invalid argument: Only string or array allowed.');
        }
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * @return array<mixed>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getId(): string
    {
        return $this->id;
    }
}

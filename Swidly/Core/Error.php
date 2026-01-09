<?php

declare(strict_types=1);

namespace Swidly\Core;

class Error
{

    public function __construct(private array $errors)
    {
    }

    public function get(string $key, $defaultValue = ''): ?string
    {
        if (!isset($this->errors[$key])) {
            return $defaultValue;
        }
        return is_array($this->errors[$key]) ? join(', ', $this->errors[$key]) : $this->errors[$key] ?? $defaultValue;
    }

    public function set(string $key, string $message): void
    {
        $this->errors[$key] = $message;
    }

    public function all(): array
    {
        return $this->errors;
    }

    public function clear(): void
    {
        $this->errors = [];
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function hasError(string $key): bool
    {
        return isset($this->errors[$key]);
    }
}
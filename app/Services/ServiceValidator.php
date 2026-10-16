<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use App\Repositories\Repository;

abstract class ServiceValidator
{
    public function __construct(
        protected Repository $repository
    ) {
    }

    abstract public function validateCreate(array $data): void;
    abstract public function validateEdit(array $data, int|string $id): void;
    abstract public function validateDelete(int|string $id): void;

    protected function throwErrors(array|null $errorMessages): void
    {
        if ($errorMessages && is_array($errorMessages) && count($errorMessages) > 0) {
            throw ValidationException::withMessages($errorMessages);
        }
    }
}

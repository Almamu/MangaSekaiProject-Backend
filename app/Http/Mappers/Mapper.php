<?php

namespace App\Http\Mappers;

/**
 * @template TModel
 */
abstract class Mapper
{
    /**
     * @param TModel $data
     * @return array<string, mixed>
     */
    abstract public function map(mixed $data): array;

    /**
     * Provided so it can be used as callable in laravel's methods
     */
    public function __invoke(mixed $data): mixed
    {
        return $this->map($data);
    }
}

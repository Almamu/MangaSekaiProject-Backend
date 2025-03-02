<?php

namespace App\Http\Responses;

use Illuminate\Http\Exceptions\HttpResponseException;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ErrorResponse', required: ['message'], properties: [
    new OA\Property(property: 'message', description: 'Translation independent error message', type: 'string'),
])]
class ErrorResponse extends \Illuminate\Http\JsonResponse
{
    final private function __construct(string $message, int $code = 400)
    {
        parent::__construct([
            'message' => $message,
        ], $code);
    }

    /**
     * Builds a new error response based on the given input
     */
    public static function fromMessage(string $message, int $code = 500): ErrorResponse
    {
        return new static($message, $code);
    }

    /**
     * Builds a new error response based on the given input
     */
    public static function throwException(string $message, int $code = 500): never
    {
        throw new HttpResponseException(static::fromMessage($message, $code));
    }
}

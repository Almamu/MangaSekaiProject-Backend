<?php

namespace App\Http\Responses;

use Illuminate\Http\Exceptions\HttpResponseException;

class ErrorResponse extends \Illuminate\Http\JsonResponse
{
    private function __construct(string $message, int $code = 400)
    {
        parent::__construct([
            'message' => $message,
        ], $code);
    }

    /**
     * Builds a new error response based on the given input
     *
     * @param string $message
     * @param int $code
     * @return static
     */
    static function fromMessage(string $message, int $code = 500): ErrorResponse
    {
        return new static($message, $code);
    }

    /**
     * Builds a new error response based on the given input
     *
     * @param string $message
     * @param int $code
     * @return void
     */
    static function throwException(string $message, int $code = 500)
    {
        throw new HttpResponseException(static::fromMessage($message, $code));
    }
};

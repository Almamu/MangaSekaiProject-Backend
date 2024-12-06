<?php

namespace App\Http\Requests\Auth;

use App\Http\Responses\ErrorResponse;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Interprets, validates and handles login requests
 */
class LoginRequest extends \Illuminate\Foundation\Http\FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * This is done by unauthorized users, so everyone should be able to use this
     */
    public function authorize(): true
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate with the request's credentials
     *
     * @return string The token
     *
     * @throws ValidationException
     */
    public function authenticate(): string
    {
        $this->ensureIsNotRateLimited();

        $token = auth()->attempt($this->only('username', 'password'));

        if (! is_string($token)) {
            RateLimiter::hit($this->throttleKey());

            $this->throwValidationError('INVALID_CREDENTIALS');
        }

        RateLimiter::clear($this->throttleKey());

        return $token;
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $this->throwValidationError('TOO_MANY_ATTEMPTS');
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    private function throttleKey(): string
    {
        $ip = $this->ip();

        if (is_null($ip)) {
            $this->throwValidationError('RATE_LIMITING_FAILED');
        }

        return $ip;
    }

    /**
     * Helper that builds a proper error message for the login requests
     *
     * @throws ValidationException
     */
    private function throwValidationError(string $error): void
    {
        throw new ValidationException($this->validator, ErrorResponse::fromMessage($error, 401));
    }
}

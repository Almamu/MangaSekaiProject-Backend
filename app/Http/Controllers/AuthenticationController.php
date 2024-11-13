<?php

namespace App\Http\Controllers;

use app\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class AuthenticationController
{
    public function login(LoginRequest $request): JsonResponse
    {
        $token = $request->authenticate();

        return $this->tokenResponse($token);
    }

    public function logout(): Response
    {
        auth()->invalidate();
        auth()->logout();

        return response(status: 200);
    }

    public function refresh(): JsonResponse
    {
        return $this->tokenResponse(auth()->refresh(true));
    }

    private function tokenResponse(string $token): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
        ]);
    }
}

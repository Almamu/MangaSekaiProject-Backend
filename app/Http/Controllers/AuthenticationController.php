<?php

namespace App\Http\Controllers;

use App\Http\OpenApi\OpenApiSpec;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

#[OA\PathItem(path: '/api/v1/auth', summary: 'Authentication')]
#[OA\Schema(schema: 'TokenResponse', required: ['token', 'token_type', 'expires_in'], properties: [
    new OA\Property(property: 'token', type: 'string'),
    new OA\Property(property: 'token_type', type: 'string'),
    new OA\Property(property: 'expires_in', type: 'integer'),
])]
#[OA\Tag(name: 'auth', description: 'Authentication')]
class AuthenticationController
{
    public function __construct(private \Illuminate\Contracts\Auth\Guard $guard, private \Illuminate\Contracts\Routing\ResponseFactory $responseFactory) {}

    #[OA\Post(
        path: '/api/v1/auth/login',
        operationId: 'login',
        description: 'Login to the application',
        summary: 'Performs login',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                schema: 'LoginRequest',
                required: ['username', 'password'],
                properties: [
                    new OA\Property(property: 'username', type: 'string'),
                    new OA\Property(property: 'password', type: 'string'),
                ]
            )
        ),
        tags: ['auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token information after successful login',
                content: new OA\JsonContent(ref: '#/components/schemas/TokenResponse')
            ),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $token = $request->authenticate();

        return $this->tokenResponse($token);
    }

    #[OA\Post(
        path: '/api/v1/auth/logout',
        operationId: 'logout',
        description: 'Logout from the application',
        summary: 'Performs logout',
        security: OpenApiSpec::SECURITY,
        tags: ['auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful logout'
            ),
        ]
    )]
    public function logout(): Response
    {
        $this->guard->invalidate();
        $this->guard->logout();

        return $this->responseFactory->make(status: 200);
    }

    #[OA\Post(
        path: '/api/v1/auth/refresh',
        operationId: 'refreshToken',
        description: 'Refreshes a valid token',
        summary: 'Refreshes a valid token',
        tags: ['auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token information after successful refresh',
                content: new OA\JsonContent(ref: '#/components/schemas/TokenResponse')
            ),
        ]
    )]
    public function refresh(): JsonResponse
    {
        return $this->tokenResponse($this->guard->refresh(true));
    }

    private function tokenResponse(string $token): \Illuminate\Http\JsonResponse
    {
        return $this->responseFactory->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard->factory()->getTTL() * 60,
        ]);
    }
}

<?php

use App\Http\Responses\ErrorResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // the web routes we have aren't really relevant and can be stripped of any middleware we don't want
        // these are related to cookies which aren't useful for us
        $middleware->web(remove: [
            Illuminate\Session\Middleware\StartSession::class,
            Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            Illuminate\Cookie\Middleware\EncryptCookies::class,
            Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            Illuminate\View\Middleware\ShareErrorsFromSession::class,
        ]);
        // prevents redirection to login
        $middleware->redirectTo(
            fn () => ErrorResponse::throwException('UNAUTHENTICATED', 401),
            fn () => ErrorResponse::throwException('AUTHENTICATED', 401)
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(fn (\Illuminate\Http\Request $request) => $request->is('api/*'));
    })->create();

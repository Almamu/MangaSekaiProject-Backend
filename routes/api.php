<?php

use App\Http\Controllers\AuthenticationController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1'], function ($router) {
    $router->group(['prefix' => 'auth'], function ($router) {
        $router->middleware('auth')->group(function () use ($router) {
            $router->post('logout', [AuthenticationController::class, 'logout']);
            $router->post('refresh', [AuthenticationController::class, 'refresh']);
        });

        $router->post('login', [AuthenticationController::class, 'login']);
    });
});

<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\SeriesController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1'], function () {
    Route::middleware(['auth'])->prefix('auth')->group(function () {
        Route::post('logout', [AuthenticationController::class, 'logout'])->name('auth.logout');
        Route::post('refresh', [AuthenticationController::class, 'refresh'])->name('auth.refresh');
        Route::post('login', [AuthenticationController::class, 'login'])->withoutMiddleware('auth')->name('auth.login');
    });

    Route::middleware(['auth'])->prefix('series')->group(function () {
        Route::get('', [SeriesController::class, 'list'])->name('series.list');
        Route::get('{serie}', [SeriesController::class, 'get'])->name('series.get');
    });
});

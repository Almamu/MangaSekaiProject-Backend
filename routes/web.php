<?php

use App\Http\Controllers\SeriesController;
use App\Http\Controllers\StaffController;

Route::middleware(['auth'])
    ->prefix('images')
    ->group(function (): void {
        Route::group(
            ['prefix' => 'series'],
            function (): void {
                Route::get('cover/{serie}', [SeriesController::class, 'cover'])->name('images.series.cover');
            },
        );
        Route::group(
            ['prefix' => 'staff'],
            function (): void {
                Route::get('avatar/{staff}', [StaffController::class, 'avatar'])->name('images.staff.avatar');
            },
        );
        Route::group(
            ['prefix' => 'pages'],
            function (): void {
                Route::get('{page}', [SeriesController::class, 'page'])->name('images.pages');
            },
        );
    });

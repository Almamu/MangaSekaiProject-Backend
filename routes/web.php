<?php

use App\Http\Controllers\SeriesController;
use App\Http\Controllers\StaffController;

Route::middleware(['auth'])->prefix('images')->group(function () {
    Route::group(['prefix' => 'series'], function () {
        Route::get('cover/{serie}', [SeriesController::class, 'cover'])->name('images.series.cover');
    });
    Route::group(['prefix' => 'staff'], function () {
        Route::get('avatar/{staff}', [StaffController::class, 'cover'])->name('images.staff.avatar');
    });
});

<?php

use App\Http\Controllers\SeriesController;

Route::middleware(['auth'])->prefix('images')->group(function () {
    Route::group(['prefix' => 'series'], function () {
        Route::get('cover/{serie}', [SeriesController::class, 'cover'])->name('images.series.cover');
    });
});

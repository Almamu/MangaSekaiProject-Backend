<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\SeriesController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1'], function (): void {
    Route::middleware(['auth'])->prefix('auth')->group(function (): void {
        Route::post('logout', [AuthenticationController::class, 'logout'])->name('auth.logout');
        Route::post('refresh', [AuthenticationController::class, 'refresh'])->withoutMiddleware('auth')->name('auth.refresh');
        Route::post('login', [AuthenticationController::class, 'login'])->withoutMiddleware('auth')->name('auth.login');
    });

    Route::middleware(['auth'])->prefix('series')->group(function (): void {
        Route::get('', [SeriesController::class, 'list'])->name('series.list');
        Route::get('recentlyUpdated', [SeriesController::class, 'recentlyUpdated'])->name('series.recentlyUpdated');
        Route::get('{serie}', [SeriesController::class, 'get'])->name('series.get');
        Route::get('{serie}/chapters', [SeriesController::class, 'chapters'])->name('series.chapters');
        Route::get('{serie}/chapters/{chapter}/pages', [SeriesController::class, 'pages'])->name('series.chapter.pages');
    });

    Route::middleware(['auth'])->prefix('staff')->group(function (): void {
        Route::get('', [StaffController::class, 'list'])->name('staff.list');
        Route::get('{staff}', [StaffController::class, 'get'])->name('staff.get');
    });

    Route::middleware(['auth'])->prefix('admin')->group(function (): void {
        Route::post('media/refresh', [MediaController::class, 'refresh'])->name('media.refresh');
        Route::get('jobs/queue', [MediaController::class, 'queue'])->name('media.queue');
    });
});

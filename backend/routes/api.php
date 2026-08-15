<?php

use App\Http\Controllers\Api\V1\PlataformaCatalogController;
use App\Http\Controllers\Api\V1\PaymentSettingsController;
use App\Http\Controllers\ApiBuscarEmailController;
use App\Http\Controllers\ApiNetflixProfileController;
use App\Support\TutorialContent;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('plataformas', PlataformaCatalogController::class)
        ->name('plataformas.index');

    Route::get('payment-settings', PaymentSettingsController::class)
        ->name('payment-settings.show');

    Route::prefix('netcode')->name('netcode.')->group(function () {
        Route::get('tutorials', fn () => response()->json([
            'data' => TutorialContent::public(),
        ]))->name('tutorials.index');

        Route::post('buscar-email', [ApiBuscarEmailController::class, 'buscar'])
            ->middleware('throttle:30,1')
            ->name('buscar-email');

        Route::post('netflix-validar', [ApiNetflixProfileController::class, 'validateAccess'])
            ->middleware('throttle:30,1')
            ->name('netflix-validar');
    });
});

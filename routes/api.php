<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\EntityController;
use App\Http\Controllers\API\EnvironmentController;
use App\Http\Controllers\API\MethodologyPageController;
use App\Http\Controllers\API\ReferentialController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::post("auth/login", "login");

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix("/auth")->name("auth.")->group(function () {
            Route::get('data', "data")->name("data");
            Route::delete('logout', "logout")->name("logout");
        });

        // Route pour changer le mot de passe (accessible même si password_change_required est true)
        Route::put('users/update-password', [UserController::class, 'updatePassword'])->name('user.update-password');

        // Routes protégées par le middleware de statut de compte
        Route::middleware('account.status')->group(function () {
            Route::prefix('methodology-pages')->name('methodology-page.')->controller(MethodologyPageController::class)->group(function () {
                Route::get('{slug}', 'showBySlug')->name('show');
                Route::put('{slug}', 'updateBySlug')->name('update');
            });

            Route::prefix('referentials')->name('referential.')->controller(ReferentialController::class)->group(function () {
                Route::get('countries', 'countries')->name('countries');
                Route::get('subsidiaries', 'subsidiaries')->name('subsidiaries');
                Route::get('departments', 'departments')->name('departments');
                Route::get('scales', 'scales')->name('scales');
                Route::get('matrix', 'matrix')->name('matrix');
                Route::get('risk-families', 'riskFamilies')->name('risk-families');
            });

            Route::prefix('environments')->name('environment.')->controller(EnvironmentController::class)->group(function () {
                Route::get('options', 'options')->name('options');
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}', 'show')->name('show');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
            });

            Route::prefix('entities')->name('entity.')->controller(EntityController::class)->group(function () {
                Route::get('by-environment/{environmentId}', 'byEnvironment')->name('by-environment');
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}', 'show')->name('show');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
            });

            // Routes utilisateurs
            Route::prefix('users')->name('user.')->controller(UserController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}', 'show')->name('show');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
            });

            // Routes supplémentaires sous autorisation
        });
    });
});

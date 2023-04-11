<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Datomatic\NovaIconField\Http\Controllers\NovaIconFieldController;

Route::group(
    config('nova-icon-field.route.nova_middleware')
        ? ['middleware' => config('nova-icon-field.route.nova_middleware')]
        : [],
    function (): void {
        Route::get('refresh', [NovaIconFieldController::class, 'refresh'])->name('refresh');
        Route::get('styles', [NovaIconFieldController::class, 'styles'])->name('styles');
        Route::get('{style}', [NovaIconFieldController::class, 'icons'])->name('icons');
    }
);

Route::group(
    config('nova-icon-field.route.icon_middleware')
        ? ['middleware' => config('nova-icon-field.route.icon_middleware')]
        : [],
    function (): void {
        Route::get('{style}/{icon}', [NovaIconFieldController::class, 'icon'])->name('icon');
    }
);

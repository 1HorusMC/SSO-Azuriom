<?php

use Azuriom\Plugin\CasdoorAuth\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('can:casdoor-auth.admin')->group(function () {
    Route::get('/', [SettingsController::class, 'show'])->name('settings');
    Route::post('/', [SettingsController::class, 'save'])->name('settings.save');
});

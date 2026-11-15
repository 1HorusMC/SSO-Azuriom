<?php

use Azuriom\Plugin\CasdoorAuth\Controllers\CasdoorController;
use Illuminate\Support\Facades\Route;

Route::prefix('casdoor')->group(function () {
    Route::get('/redirect', [CasdoorController::class, 'redirect'])->name('casdoor.redirect');
    Route::get('/callback', [CasdoorController::class, 'callback'])->name('casdoor.callback');
});

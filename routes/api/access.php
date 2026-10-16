<?php

use App\Http\Controllers\Access\AccessController;

Route::controller(AccessController::class)->group(function () {
    Route::post('/login', 'login');
});

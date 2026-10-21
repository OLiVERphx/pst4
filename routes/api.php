<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController as ApiAuthController;

Route::prefix('auth')->group(function () {
    // Mismos límites que el login y el registro de la web: sin ellos esta ruta permitía
    // probar contraseñas sin límite, saltándose el throttle y el captcha del formulario.
    Route::post('register', [ApiAuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('login', [ApiAuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('logout', [ApiAuthController::class, 'logout'])->middleware('auth:sanctum');
});

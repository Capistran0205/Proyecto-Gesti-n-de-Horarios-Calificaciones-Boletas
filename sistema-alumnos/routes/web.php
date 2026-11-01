<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordRecoveryController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/panel');
// Interfaz de inicio de sesión (usuario no autenticado en el sistema)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'mostrarLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/recuperar', [PasswordRecoveryController::class, 'formulario'])->name('password.request');
    Route::post('/recuperar', [PasswordRecoveryController::class, 'enviar'])->name('password.email');
    Route::get('/restablecer/{token}', [PasswordRecoveryController::class, 'formularioRestablecer'])->name('password.reset');
    Route::post('/restablecer', [PasswordRecoveryController::class, 'restablecer'])->name('password.update');
});

// Panel correspondiente para usuarios autenticados
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/panel', fn () => redirect()->route(auth()->user()->rutaPanel()))->name('panel');
// Rutas asociadas a las funcionalidades para cada tipo de usuario
    // RN-17: cada ruta solo la ve su rol
    Route::get('/superusuario', fn () => view('panel.inicio'))->middleware('rol:0')->name('panel.superusuario');
    Route::get('/administrador', fn () => view('panel.inicio'))->middleware('rol:1')->name('panel.administrador');
    Route::get('/docente', fn () => view('panel.inicio'))->middleware('rol:2')->name('panel.docente');
    Route::get('/alumno', fn () => view('panel.inicio'))->middleware('rol:3')->name('panel.alumno');
});
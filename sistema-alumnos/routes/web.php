<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CambioContrasenaController;
use App\Http\Controllers\PasswordRecoveryController;
use App\Http\Controllers\SuperUsuario\AdministradorController;
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

    // Cambio obligatorio de la contraseña temporal (queda fuera de 'cambio.contrasena')
    Route::get('/cambiar-contrasena', [CambioContrasenaController::class, 'mostrar'])->name('contrasena.cambiar');
    Route::put('/cambiar-contrasena', [CambioContrasenaController::class, 'actualizar'])->name('contrasena.actualizar');

    // Todo lo demás exige haber cambiado la contraseña temporal
    Route::middleware('cambio.contrasena')->group(function () {
        Route::get('/panel', fn () => redirect()->route(auth()->user()->rutaPanel()))->name('panel');
        // Rutas asociadas a las funcionalidades para cada tipo de usuario
        // RN-17: cada ruta solo la ve su rol
        Route::get('/superusuario', fn () => view('panel.inicio'))->middleware('rol:0')->name('panel.superusuario');
        Route::get('/administrador', fn () => view('panel.inicio'))->middleware('rol:1')->name('panel.administrador');
        Route::get('/docente', fn () => view('panel.inicio'))->middleware('rol:2')->name('panel.docente');
        Route::get('/alumno', fn () => view('panel.inicio'))->middleware('rol:3')->name('panel.alumno');

        // HU-02: gestión de administradores (solo SuperUsuario)
        Route::middleware('rol:0')
            ->prefix('superusuario/administradores')
            ->name('superusuario.administradores.')
            ->controller(AdministradorController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('crear', 'create')->name('create');
                Route::get('contrasena-temporal', 'contrasena')->name('contrasena'); // antes de {usuario}
                Route::post('/', 'store')->name('store');
                Route::get('{usuario}', 'show')->whereNumber('usuario')->name('show');
                Route::get('{usuario}/editar', 'edit')->whereNumber('usuario')->name('edit');
                Route::put('{usuario}', 'update')->whereNumber('usuario')->name('update');
                Route::patch('{usuario}/baja', 'baja')->whereNumber('usuario')->name('baja');
                Route::patch('{usuario}/reactivar', 'reactivar')->whereNumber('usuario')->name('reactivar');
            });
    });
});

<?php

use App\Http\Controllers\Admin\AlumnoController;
use App\Http\Controllers\Admin\DisponibilidadDocenteController;
use App\Http\Controllers\Admin\DocenteController;
use App\Http\Controllers\Admin\MateriaController;
use App\Http\Controllers\Admin\PeriodoEscolarController;
use App\Http\Controllers\Admin\SalonController;
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

        // Módulo 2: aulas y periodos escolares (solo Administrador)
        Route::middleware('rol:1')->prefix('admin')->name('admin.')->group(function () {
            Route::prefix('aulas')->name('aulas.')->controller(SalonController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('crear', 'create')->name('create'); // antes de {salon}
                Route::post('/', 'store')->name('store');
                Route::get('{salon}/editar', 'edit')->whereNumber('salon')->name('edit');
                Route::put('{salon}', 'update')->whereNumber('salon')->name('update');
            });

            Route::prefix('periodos')->name('periodos.')->controller(PeriodoEscolarController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('crear', 'create')->name('create'); // antes de {periodo}
                Route::post('/', 'store')->name('store');
                Route::get('{periodo}/editar', 'edit')->whereNumber('periodo')->name('edit');
                Route::put('{periodo}', 'update')->whereNumber('periodo')->name('update');
            });

            // Módulo 3: materias (HU-05)
            Route::prefix('materias')->name('materias.')->controller(MateriaController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('crear', 'create')->name('create'); // antes de {materia}
                Route::post('/', 'store')->name('store');
                Route::get('{materia}', 'show')->whereNumber('materia')->name('show');
                Route::get('{materia}/editar', 'edit')->whereNumber('materia')->name('edit');
                Route::put('{materia}', 'update')->whereNumber('materia')->name('update');
                Route::patch('{materia}/baja', 'baja')->whereNumber('materia')->name('baja');
                Route::patch('{materia}/reactivar', 'reactivar')->whereNumber('materia')->name('reactivar');
            });

            // Módulo 5: alumnos (HU-04)
            Route::prefix('alumnos')->name('alumnos.')->controller(AlumnoController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('crear', 'create')->name('create');
                Route::get('contrasena-temporal', 'contrasena')->name('contrasena'); // antes de {alumno}
                Route::post('/', 'store')->name('store');
                Route::get('{alumno}', 'show')->whereNumber('alumno')->name('show');
                Route::get('{alumno}/editar', 'edit')->whereNumber('alumno')->name('edit');
                Route::put('{alumno}', 'update')->whereNumber('alumno')->name('update');
                Route::patch('{alumno}/baja', 'baja')->whereNumber('alumno')->name('baja');
                Route::patch('{alumno}/reactivar', 'reactivar')->whereNumber('alumno')->name('reactivar');
            });

            // Módulo 4: docentes (HU-03) y su disponibilidad
            Route::prefix('docentes')->name('docentes.')->group(function () {
                Route::controller(DocenteController::class)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('crear', 'create')->name('create');
                    Route::get('contrasena-temporal', 'contrasena')->name('contrasena'); // antes de {docente}
                    Route::post('/', 'store')->name('store');
                    Route::get('{docente}', 'show')->whereNumber('docente')->name('show');
                    Route::get('{docente}/editar', 'edit')->whereNumber('docente')->name('edit');
                    Route::put('{docente}', 'update')->whereNumber('docente')->name('update');
                    Route::patch('{docente}/baja', 'baja')->whereNumber('docente')->name('baja');
                    Route::patch('{docente}/reactivar', 'reactivar')->whereNumber('docente')->name('reactivar');
                });

                Route::controller(DisponibilidadDocenteController::class)->group(function () {
                    Route::get('{docente}/disponibilidad', 'edit')->whereNumber('docente')->name('disponibilidad.edit');
                    Route::put('{docente}/disponibilidad', 'update')->whereNumber('docente')->name('disponibilidad.update');
                });
            });
        });
    });
});

@php
    $usuario = auth()->user();

    $opciones = match ($usuario->rol) {
        0 => [
            ['Administradores', 'Registrar, consultar, modificar y dar de baja administradores.'],
            ['Restablecer contraseñas', 'Restablecer la contraseña de los demás usuarios del sistema.'],
        ],
        1 => [
            ['Gestión de Docentes', 'Registrar, consultar, modificar y dar de baja docentes.'],
            ['Gestión de Alumnos', 'Registrar, consultar, modificar y dar de baja alumnos.'],
            ['Gestión de Materias', 'Registrar, consultar, modificar y dar de baja materias.'],
        ],
        default => [],
    };
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel {{ $usuario->nombreRol() }}</title>
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
    @vite('resources/js/app.js')
</head>
<body class="panel">
    <header class="cabecera">
        <img src="{{ asset('img/logoprepa.png') }}" alt="Escuela de Bachilleres Antonio María de Rivera">
        <div class="usuario-info">
            <div>
                <strong>{{ $usuario->nombreCompleto() }}</strong>
                <small>{{ $usuario->nombreRol() }}</small>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="boton-salir">Cerrar sesión</button>
            </form>
        </div>
    </header>

    <main class="contenido">
        @if (count($opciones) > 0)
            <p class="migas">Panel {{ $usuario->nombreRol() }}</p>
            <h1>Menú principal</h1>
            <p class="texto">Selecciona una opción para continuar.</p>

            <div class="tarjetas">
                @foreach ($opciones as [$titulo, $descripcion])
                    <section class="opcion">
                        <h2>{{ $titulo }}</h2>
                        <p>{{ $descripcion }}</p>
                        <button type="button" class="boton-oscuro" disabled>Entrar</button>
                    </section>
                @endforeach
            </div>
        @else
            <div class="mensaje-central">
                <h2>Esta es la interfaz del {{ strtolower($usuario->nombreRol()) }}</h2>
            </div>
        @endif
    </main>
</body>
</html>
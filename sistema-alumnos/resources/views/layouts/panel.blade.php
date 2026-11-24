@php
    $usuario = auth()->user();
    $tituloErrores = trim($__env->yieldContent('titulo-errores')) ?: 'No se pudo completar la operación';
    $introErrores = trim($__env->yieldContent('intro-errores')) ?: 'Revisa y corrige lo siguiente:';
    $tituloExito = trim($__env->yieldContent('titulo-exito')) ?: 'Operación exitosa';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Panel ' . $usuario->nombreRol())</title>
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
        @yield('contenido')
    </main>

    {{-- Mensajes del sistema: siempre en la misma ventana emergente --}}
    @if (session('exito'))
        <x-modal-mensaje tipo="exito" :titulo="$tituloExito" :mensaje="session('exito')" :contrasena="$contrasenaEntregada ?? null" />
    @elseif (session('error'))
        <x-modal-mensaje tipo="error" :titulo="$tituloErrores" :mensaje="session('error')" />
    @elseif ($errors->any())
        <x-modal-mensaje tipo="error" :titulo="$tituloErrores" :intro="$introErrores" :items="$errors->all()" />
    @endif
</body>
</html>

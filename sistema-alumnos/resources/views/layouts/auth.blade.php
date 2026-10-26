<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Portal escolar')</title>
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
    @vite('resources/js/app.js')
</head>
<body class="fondo">
    <main class="tarjeta">
        <img class="logo" src="{{ asset('img/logoprepa.png') }}" alt="Escuela de Bachilleres Antonio María de Rivera">
        @yield('contenido')
    </main>
</body>
</html>
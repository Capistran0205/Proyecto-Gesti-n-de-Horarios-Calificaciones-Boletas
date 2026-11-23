<!-- Plantilla para interfaz construida en blade con php -->
@extends('layouts.auth')

@section('titulo', 'Iniciar sesión')

@section('contenido')
    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        @error('login')
            <div class="alerta alerta-error" role="alert">{{ $message }}</div>
        @enderror
        <h3>Escribe tu nombre de usuario/correo:</h3>
        <input class="campo @error('login') invalido @enderror" type="text" name="identificador"
            value="{{ old('identificador') }}" placeholder="Nombre de usuario o correo" autocomplete="username" autofocus>
        <h3>Escribe tu contraseña:</h3>
        <input class="campo @error('login') invalido @enderror" type="password" name="contrasena" placeholder="Contraseña"
            autocomplete="current-password">

        <div>
            <button type="submit" class="boton boton-principal">Acceder</button>
            <p>¿Olvidaste tu contraseña?</p>
            <a class="boton boton-borde" href="{{ route('password.request') }}">Recuperar contraseña</a>
        </div>
    </form>
@endsection

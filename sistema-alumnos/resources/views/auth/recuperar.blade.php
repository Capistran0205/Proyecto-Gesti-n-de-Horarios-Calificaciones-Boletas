@extends('layouts.auth')

@section('titulo', 'Recuperar contraseña')

@section('contenido')
    <h1>Recuperar contraseña</h1>

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf

        @error('identificador')
            <div class="alerta alerta-error" role="alert">{{ $message }}</div>
        @else
            <p class="texto">Ingresa tu número de usuario o tu correo. Te enviaremos un mensaje de recuperación al correo registrado.</p>
        @enderror

        <input class="campo @error('identificador') invalido @enderror" type="text" name="identificador"
               value="{{ old('identificador') }}" placeholder="Número de usuario o correo" autofocus>

        <button type="submit" class="boton boton-principal">Enviar mensaje de recuperación</button>
        <a class="boton boton-borde" href="{{ route('login') }}">Volver al inicio de sesión</a>
    </form>
@endsection
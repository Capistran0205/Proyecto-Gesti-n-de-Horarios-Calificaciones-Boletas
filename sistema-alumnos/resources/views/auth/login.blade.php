@extends('layouts.auth')

@section('titulo', 'Iniciar sesión')

@section('contenido')
    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        @error('login')
            <div class="alerta alerta-error" role="alert">{{ $message }}</div>
        @enderror

        <input class="campo @error('login') invalido @enderror" type="text" name="identificador"
               value="{{ old('identificador') }}" placeholder="Nombre de usuario o correo"
               autocomplete="username" autofocus>

        <input class="campo @error('login') invalido @enderror" type="password" name="contrasena"
               placeholder="Contraseña" autocomplete="current-password">

        <button type="submit" class="boton boton-principal">Acceder</button>
        <a class="boton boton-borde" href="{{ route('password.request') }}">Recuperar contraseña</a>
    </form>
@endsection
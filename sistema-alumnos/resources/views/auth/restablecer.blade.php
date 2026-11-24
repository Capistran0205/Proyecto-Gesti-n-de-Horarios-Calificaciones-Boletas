@extends('layouts.auth')

@section('titulo', 'Crear nueva contraseña')

@section('contenido')
    <h1>Crear nueva contraseña</h1>

    <form method="POST" action="{{ route('password.update') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="usuario" value="{{ $usuario }}">

        @if ($errors->any())
            <div class="alerta alerta-error" role="alert">{{ $errors->first() }}</div>
        @else
            <p class="texto">Usuario <strong>{{ $usuario }}</strong></p>
        @endif

        @include('auth._campos-nueva-contrasena')

        <button type="submit" class="boton boton-principal">Guardar contraseña</button>
    </form>
@endsection
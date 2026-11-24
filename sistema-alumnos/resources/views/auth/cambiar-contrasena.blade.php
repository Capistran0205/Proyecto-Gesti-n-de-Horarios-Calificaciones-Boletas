@extends('layouts.auth')

@section('titulo', 'Cambiar contraseña')

@section('contenido')
    <h1>Crear nueva contraseña</h1>

    <form method="POST" action="{{ route('contrasena.actualizar') }}" novalidate>
        @csrf
        @method('PUT')

        @if ($errors->any())
            <div class="alerta alerta-error" role="alert">{{ $errors->first() }}</div>
        @else
            <p class="texto">Usuario <strong>{{ auth()->user()->usuario }}</strong></p>
            <p class="texto">Estás usando una contraseña temporal. Crea una nueva para continuar.</p>
        @endif

        @include('auth._campos-nueva-contrasena')

        <button type="submit" class="boton boton-principal">Guardar contraseña</button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="boton boton-borde">Cerrar sesión</button>
    </form>
@endsection

@extends('layouts.panel')

@php
    $usuario = auth()->user();

    // [título, descripción, ruta (null = aún no disponible)]
    $opciones = match ($usuario->rol) {
        0 => [
            ['Administradores', 'Registrar, consultar, modificar y dar de baja administradores.', route('superusuario.administradores.index')],
            ['Restablecer contraseñas', 'Restablecer la contraseña de los demás usuarios del sistema.', null],
        ],
        1 => [
            ['Gestión de Docentes', 'Registrar, consultar, modificar y dar de baja docentes.', route('admin.docentes.index')],
            ['Gestión de Alumnos', 'Registrar, consultar, modificar y dar de baja alumnos.', null],
            ['Gestión de Materias', 'Registrar, consultar, modificar y dar de baja materias.', route('admin.materias.index')],
        ],
        default => [],
    };
@endphp

@section('titulo', 'Panel ' . $usuario->nombreRol())

@section('contenido')
    @if (count($opciones) > 0)
        <p class="migas">Panel {{ $usuario->nombreRol() }}</p>
        <h1>Menú principal</h1>
        <p class="texto">Selecciona una opción para continuar.</p>

        <div class="tarjetas">
            @foreach ($opciones as [$titulo, $descripcion, $ruta])
                <section class="opcion">
                    <h2>{{ $titulo }}</h2>
                    <p>{{ $descripcion }}</p>
                    @if ($ruta)
                        <a href="{{ $ruta }}" class="boton-oscuro">Entrar</a>
                    @else
                        <button type="button" class="boton-oscuro" disabled>Entrar</button>
                    @endif
                </section>
            @endforeach
        </div>
    @else
        <div class="mensaje-central">
            <h2>Esta es la interfaz del {{ strtolower($usuario->nombreRol()) }}</h2>
        </div>
    @endif
@endsection

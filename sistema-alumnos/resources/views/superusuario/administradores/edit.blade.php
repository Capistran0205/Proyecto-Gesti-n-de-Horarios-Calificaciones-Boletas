@extends('layouts.panel')

@section('titulo', 'Modificar administrador')
@section('titulo-errores', 'No se pudieron guardar los cambios')

@section('contenido')
<form method="POST" action="{{ route('superusuario.administradores.update', $administrador) }}" class="formulario-tarjeta" novalidate>
    @csrf
    @method('PUT')
    <h1>Modificar administrador</h1>

    @include('superusuario.administradores._form', ['modo' => 'editar'])

    <div class="nota-vacia">La contraseña solo cambia por restablecimiento</div>

    <div class="meta">
        Registrado · {{ $administrador->fecha_registro->format('d/m/Y H:i') }}<br>
        Última modificación · {{ $administrador->fecha_modificacion->format('d/m/Y H:i') }}
    </div>

    <div class="formulario-acciones">
        <a href="{{ route('superusuario.administradores.index') }}" class="btn btn-borde">Cancelar</a>
        <button type="submit" class="btn btn-primario">Guardar cambios</button>
    </div>
</form>
@endsection

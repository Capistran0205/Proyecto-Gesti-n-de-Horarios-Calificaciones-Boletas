@extends('layouts.panel')

@section('titulo', 'Modificar docente')
@section('titulo-errores', 'No se pudieron guardar los cambios')

@php
    // Estudios: lo reenviado si el formulario falló; si no, los guardados
    $estudiosIniciales = old('estudios') !== null
        ? collect(old('estudios'))->map(fn ($e) => ['grado' => (string) ($e['grado_estudio'] ?? ''), 'descripcion' => $e['descripcion'] ?? ''])->values()->all()
        : $docente->docente->estudios->sortBy('id')->map(fn ($e) => ['grado' => (string) $e->grado_estudio, 'descripcion' => $e->descripcion])->values()->all();
@endphp

@section('contenido')
@include('admin.docentes._scripts')

<form method="POST" action="{{ route('admin.docentes.update', $docente) }}" class="formulario-tarjeta" novalidate
      x-data="{ ...estudiosDocente(@js($estudiosIniciales), 4) }">
    @csrf
    @method('PUT')
    <h1>Modificar docente</h1>

    @include('admin.docentes._form', ['modo' => 'editar'])

    <p class="ayuda">El correo, la CURP y el RFC deben ser únicos: no pueden repetirse con los de otro docente.</p>
    <div class="nota-vacia">La contraseña solo cambia por restablecimiento</div>

    <div class="meta">
        Registrado: {{ $docente->fecha_registro->format('d/m/Y H:i') }}<br>
        Última modificación: {{ $docente->fecha_modificacion->format('d/m/Y H:i') }}
    </div>

    <div class="formulario-acciones">
        <a href="{{ route('admin.docentes.index') }}" class="btn btn-borde">Cancelar</a>
        <button type="submit" class="btn btn-primario">Guardar cambios</button>
    </div>
</form>
@endsection

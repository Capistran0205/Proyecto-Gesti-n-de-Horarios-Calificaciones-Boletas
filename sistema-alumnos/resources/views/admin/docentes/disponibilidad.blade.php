@extends('layouts.panel')

@section('titulo', 'Modificar disponibilidad')
@section('titulo-errores', 'No se pudo guardar la disponibilidad')

@php
    // Lo reenviado si el formulario falló; si no, la disponibilidad guardada del docente
    $reenviada = old('disponibilidad');
    $rangosIniciales = [];
    foreach ($rangos as $dia => $rango) {
        $rangosIniciales[$dia] = is_array($reenviada)
            ? [
                'activo' => filter_var($reenviada[$dia]['activo'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'desde' => $reenviada[$dia]['desde'] ?? $rango['desde'],
                'hasta' => $reenviada[$dia]['hasta'] ?? $rango['hasta'],
            ]
            : $rango;
    }
@endphp

@section('contenido')
@include('admin.docentes._scripts')

<form method="POST" action="{{ route('admin.docentes.disponibilidad.update', $docente) }}" class="formulario-tarjeta tarjeta-media" novalidate
      x-data="{ ...disponibilidadDocente(@js($rangosIniciales)), habilitado() { return true; } }"
      @submit="if (!validarDisp()) $event.preventDefault()">
    @csrf
    @method('PUT')
    <h1>Modificar Disponibilidad Semanal</h1>
    <p class="subtitulo">Cambio de Disponibilidad de Docente Registrado · Marca los días en que labora (lunes a viernes) y su horario de cada día.</p>

    @include('admin.docentes._cuadricula-disponibilidad')

    <div class="formulario-acciones">
        <a href="{{ route('admin.docentes.edit', $docente) }}" class="btn btn-borde">Cancelar</a>
        <button type="submit" class="btn btn-primario">Guardar disponibilidad</button>
    </div>
</form>
@endsection

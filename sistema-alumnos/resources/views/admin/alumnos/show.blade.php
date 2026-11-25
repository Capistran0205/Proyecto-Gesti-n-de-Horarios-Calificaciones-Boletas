@extends('layouts.panel')

@section('titulo', 'Alumno')

@section('contenido')
<div class="formulario-tarjeta">
    <h1>{{ $alumno->nombre_completo }}</h1>

    <div class="fila">
        <dl class="dato-lectura"><dt>Matrícula</dt><dd>{{ $alumno->usuario }}</dd></dl>
        <dl class="dato-lectura">
            <dt>Estado</dt>
            <dd>
                @if ($alumno->estado === \App\Models\User::ESTADO_ACTIVO)
                    <span class="insignia activo">Activo</span>
                @else
                    <span class="insignia baja">De baja</span>
                @endif
            </dd>
        </dl>
    </div>
    <dl class="dato-lectura"><dt>Correo electrónico</dt><dd>{{ $alumno->correo_electronico }}</dd></dl>
    <div class="fila">
        <dl class="dato-lectura"><dt>CURP</dt><dd>{{ $alumno->alumno->curp }}</dd></dl>
        <dl class="dato-lectura"><dt>NSS</dt><dd>{{ $alumno->alumno->nss ?? 'Sin NSS' }}</dd></dl>
    </div>
    <div class="fila">
        <dl class="dato-lectura"><dt>Generación (año de ingreso)</dt><dd>{{ $alumno->alumno->anio_ingreso }}</dd></dl>
        <dl class="dato-lectura"><dt>Semestre</dt><dd>{{ $alumno->alumno->semestre }}</dd></dl>
    </div>
    <div class="fila">
        <dl class="dato-lectura"><dt>Área de especialidad</dt><dd>{{ $alumno->alumno->especialidad->descripcion ?? 'No aplica (desde 5.º semestre)' }}</dd></dl>
        <dl class="dato-lectura"><dt>Grupo del periodo vigente</dt><dd>{{ $grupoVigente->nombre ?? 'Sin grupo' }}</dd></dl>
    </div>

    <div class="meta">
        Registrado: {{ $alumno->fecha_registro->format('d/m/Y H:i') }}<br>
        Última modificación: {{ $alumno->fecha_modificacion->format('d/m/Y H:i') }}
    </div>

    <div class="formulario-acciones">
        <a href="{{ route('admin.alumnos.index') }}" class="btn btn-borde">Volver</a>
        @if ($alumno->estado === \App\Models\User::ESTADO_ACTIVO)
            <a href="{{ route('admin.alumnos.edit', $alumno) }}" class="btn btn-primario">Editar</a>
        @endif
    </div>
</div>
@endsection

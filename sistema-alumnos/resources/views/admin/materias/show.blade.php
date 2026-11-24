@extends('layouts.panel')

@section('titulo', 'Materia')

@section('contenido')
<div class="formulario-tarjeta">
    <h1>{{ $materia->nombre }}</h1>

    <div class="fila">
        <dl class="dato-lectura"><dt>Clave</dt><dd>{{ $materia->clave }}</dd></dl>
        <dl class="dato-lectura"><dt>Semestre</dt><dd>{{ $materia->semestre }}</dd></dl>
    </div>
    <div class="fila">
        <dl class="dato-lectura"><dt>Tipo</dt><dd>{{ $materia->tipo_texto }}</dd></dl>
        <dl class="dato-lectura"><dt>Horas semanales</dt><dd>{{ $materia->horas_semana }}</dd></dl>
    </div>
    <dl class="dato-lectura"><dt>Área disciplinar</dt><dd>{{ $materia->areaDisciplinar->descripcion }}</dd></dl>
    <dl class="dato-lectura"><dt>Especialidad</dt><dd>{{ $materia->especialidad->descripcion ?? '—' }}</dd></dl>
    <dl class="dato-lectura">
        <dt>Estado</dt>
        <dd>
            @if ($materia->estado === \App\Models\Materia::ESTADO_ACTIVA)
                <span class="insignia activo">Activo</span>
            @else
                <span class="insignia baja">De baja</span>
            @endif
        </dd>
    </dl>

    <div class="meta">
        Registrado: {{ $materia->fecha_registro->format('d/m/Y H:i') }}<br>
        Última modificación: {{ $materia->fecha_modificacion->format('d/m/Y H:i') }}
    </div>

    <div class="formulario-acciones">
        <a href="{{ route('admin.materias.index') }}" class="btn btn-borde">Volver</a>
        @if ($materia->estado === \App\Models\Materia::ESTADO_ACTIVA)
            <a href="{{ route('admin.materias.edit', $materia) }}" class="btn btn-primario">Editar</a>
        @endif
    </div>
</div>
@endsection

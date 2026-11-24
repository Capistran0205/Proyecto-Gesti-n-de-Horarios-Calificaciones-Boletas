@extends('layouts.panel')

@section('titulo', 'Docente')

@section('contenido')
<div class="formulario-tarjeta">
    <h1>{{ $docente->nombre_completo }}</h1>

    <div class="fila">
        <dl class="dato-lectura"><dt>Número de empleado</dt><dd>{{ $docente->usuario }}</dd></dl>
        <dl class="dato-lectura">
            <dt>Estado</dt>
            <dd>
                @if ($docente->estado === \App\Models\User::ESTADO_ACTIVO)
                    <span class="insignia activo">Activo</span>
                @else
                    <span class="insignia baja">De baja</span>
                @endif
            </dd>
        </dl>
    </div>
    <dl class="dato-lectura"><dt>Correo electrónico</dt><dd>{{ $docente->correo_electronico }}</dd></dl>
    <div class="fila">
        <dl class="dato-lectura"><dt>CURP</dt><dd>{{ $docente->docente->curp }}</dd></dl>
        <dl class="dato-lectura"><dt>RFC</dt><dd>{{ $docente->docente->rfc }}</dd></dl>
    </div>
    <div class="fila">
        <dl class="dato-lectura"><dt>Área disciplinar</dt><dd>{{ $docente->docente->areaDisciplinar->descripcion }}</dd></dl>
        <dl class="dato-lectura"><dt>Horas de plaza</dt><dd>{{ $docente->docente->horas_plaza }}{{ $docente->docente->esTiempoCompleto() ? ' (tiempo completo)' : '' }}</dd></dl>
    </div>

    <dl class="dato-lectura">
        <dt>Estudios</dt>
        @foreach ($docente->docente->estudios->sortBy('id') as $estudio)
            <dd>{{ $estudio->grado_texto }} · {{ $estudio->descripcion }}</dd>
        @endforeach
    </dl>
    <dl class="dato-lectura"><dt>Disponibilidad semanal</dt><dd>{{ $docente->docente->resumenDisponibilidad() }}</dd></dl>
    <dl class="dato-lectura">
        <dt>Materias que puede impartir</dt>
        <dd>{{ $docente->docente->materias->isEmpty() ? 'Sin materias asignadas' : $docente->docente->materias->sortBy('clave')->map(fn ($m) => "{$m->clave} · {$m->nombre}")->implode(', ') }}</dd>
    </dl>

    <div class="meta">
        Registrado: {{ $docente->fecha_registro->format('d/m/Y H:i') }}<br>
        Última modificación: {{ $docente->fecha_modificacion->format('d/m/Y H:i') }}
    </div>

    <div class="formulario-acciones">
        <a href="{{ route('admin.docentes.index') }}" class="btn btn-borde">Volver</a>
        @if ($docente->estado === \App\Models\User::ESTADO_ACTIVO)
            <a href="{{ route('admin.docentes.edit', $docente) }}" class="btn btn-primario">Editar</a>
        @endif
    </div>
</div>
@endsection

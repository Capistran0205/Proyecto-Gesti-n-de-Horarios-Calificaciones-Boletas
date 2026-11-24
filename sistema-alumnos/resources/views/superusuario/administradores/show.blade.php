@extends('layouts.panel')

@section('titulo', 'Administrador')

@section('contenido')
<div class="formulario-tarjeta">
    <h1>{{ $administrador->nombre_completo }}</h1>

    <dl class="dato-lectura"><dt>Número de empleado</dt><dd>{{ $administrador->usuario }}</dd></dl>
    <dl class="dato-lectura"><dt>Correo electrónico</dt><dd>{{ $administrador->correo_electronico }}</dd></dl>
    <div class="fila">
        <dl class="dato-lectura"><dt>CURP</dt><dd>{{ $administrador->administrador->curp }}</dd></dl>
        <dl class="dato-lectura"><dt>RFC</dt><dd>{{ $administrador->administrador->rfc }}</dd></dl>
    </div>
    <dl class="dato-lectura">
        <dt>Estado</dt>
        <dd>
            @if ($administrador->estado === \App\Models\User::ESTADO_ACTIVO)
                <span class="insignia activo">Activo</span>
            @else
                <span class="insignia baja">De baja</span>
            @endif
        </dd>
    </dl>

    <div class="meta">
        Registrado · {{ $administrador->fecha_registro->format('d/m/Y H:i') }}<br>
        Última modificación · {{ $administrador->fecha_modificacion->format('d/m/Y H:i') }}
    </div>

    <div class="formulario-acciones">
        <a href="{{ route('superusuario.administradores.index') }}" class="btn btn-borde">Volver</a>
        @if ($administrador->estado === \App\Models\User::ESTADO_ACTIVO)
            <a href="{{ route('superusuario.administradores.edit', $administrador) }}" class="btn btn-primario">Editar</a>
        @endif
    </div>
</div>
@endsection

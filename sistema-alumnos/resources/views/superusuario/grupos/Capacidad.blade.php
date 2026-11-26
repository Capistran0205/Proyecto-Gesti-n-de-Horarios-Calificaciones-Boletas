@extends('layouts.panel')

@section('titulo', 'Capacidad del grupo')
@section('titulo-errores', 'No se pudo guardar la capacidad')

@section('contenido')
<form method="POST" action="{{ route('superusuario.grupos.capacidad.update', $grupo) }}" class="formulario-tarjeta" novalidate>
    @csrf
    @method('PUT')
    <h1>Capacidad del grupo {{ $grupo->nombre_grupo }}</h1>

    {{-- Mismo diseño que "Modificar grupo": aquí solo se edita la capacidad --}}
    <div class="campo-grupo">
        <label class="etiqueta" for="periodo">Periodo</label>
        <input class="entrada" type="text" id="periodo" value="{{ $grupo->periodo->nombre }}" disabled>
    </div>

    <div class="fila">
        <div class="campo-grupo">
            <label class="etiqueta" for="semestre">Semestre</label>
            <input class="entrada" type="text" id="semestre" value="{{ $grupo->semestre }}" disabled>
        </div>
        <div class="campo-grupo">
            <label class="etiqueta" for="letra">Letra</label>
            <input class="entrada" type="text" id="letra" value="{{ $grupo->letra }}" disabled>
        </div>
    </div>

    <div class="campo-grupo">
        <label class="etiqueta" for="id_especialidad">Especialidad</label>
        <input class="entrada" type="text" id="id_especialidad" value="{{ $grupo->especialidad->descripcion ?? 'Solo para grupos de 5.º y 6.º semestre' }}" disabled>
    </div>

    <div class="fila">
        <div class="campo-grupo">
            <label class="etiqueta" for="capacidad_max">Capacidad máxima *</label>
            <input class="entrada @error('capacidad_max') invalido @enderror" type="number" id="capacidad_max" name="capacidad_max"
                   min="{{ max(1, $grupo->alumnos_count) }}" max="{{ \App\Models\Grupo::CAPACIDAD_MAXIMA_BD }}" step="1" inputmode="numeric"
                   value="{{ old('capacidad_max', $grupo->capacidad_max) }}" autofocus>
        </div>
        <div class="campo-grupo">
            <label class="etiqueta" for="inscritos">Alumnos inscritos</label>
            <input class="entrada" type="text" id="inscritos" value="{{ $grupo->alumnos_count }}" readonly>
        </div>
    </div>

    <p class="ayuda">La capacidad no puede ser menor a los alumnos ya inscritos y no puede pasar de {{ \App\Models\Grupo::CAPACIDAD_MAXIMA_BD }}. El administrador no puede modificarla.</p>

    <div class="meta">
        Registrado: {{ $grupo->fecha_registro->format('d/m/Y H:i') }}<br>
        Última modificación: {{ $grupo->fecha_modificacion->format('d/m/Y H:i') }}
    </div>

    <div class="formulario-acciones">
        <a href="{{ route('superusuario.grupos.index', ['periodo' => $grupo->id_periodo]) }}" class="btn btn-borde">Cancelar</a>
        <button type="submit" class="btn btn-primario">Guardar capacidad</button>
    </div>
</form>
@endsection
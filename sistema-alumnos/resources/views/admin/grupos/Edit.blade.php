@extends('layouts.panel')

@php
    // Mensaje específico cuando el único problema es que el grupo ya existe en el periodo
    $grupoRepetido = $errors->count() === 1 && str_contains((string) $errors->first('letra'), 'ya está registrado');
@endphp

@section('titulo', 'Modificar grupo')
@section('titulo-errores', 'No se pudieron guardar los cambios')
@section('intro-errores', $grupoRepetido ? 'La combinación de semestre y letra debe ser única dentro del periodo y ya existe:' : 'Revisa y corrige lo siguiente:')

@section('contenido')
<form method="POST" action="{{ route('admin.grupos.update', $grupo) }}" class="formulario-tarjeta" novalidate
      x-data="{
          semestre: @js((string) old('semestre', $grupo->semestre)),
          letra: @js((string) old('letra', $grupo->letra)),
          especialidad: @js((string) old('id_especialidad', $grupo->id_especialidad ?? '')),
          requiereEspecialidad() { return Number(this.semestre) >= 5; }
      }">
    @csrf
    @method('PUT')
    <h1>Modificar grupo</h1>

    @include('admin.grupos._form', ['modo' => 'editar'])

    <p class="ayuda">No puede repetirse la combinación de semestre y letra dentro del mismo periodo. La capacidad solo puede ampliarla el SuperUsuario.</p>

    @if ($tieneAlumnos)
        <div class="nota-vacia">Tiene alumnos inscritos: el semestre y la especialidad no se pueden cambiar. Para cambiarlos, primero quita a los alumnos del grupo.</div>
    @endif

    <div class="meta">
        Registrado: {{ $grupo->fecha_registro->format('d/m/Y H:i') }}<br>
        Última modificación: {{ $grupo->fecha_modificacion->format('d/m/Y H:i') }}
    </div>

    <div class="formulario-acciones">
        <a href="{{ route('admin.grupos.show', $grupo) }}" class="btn btn-borde">Cancelar</a>
        <button type="submit" class="btn btn-primario">Guardar cambios</button>
    </div>
</form>
@endsection
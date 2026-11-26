@extends('layouts.panel')

@php
    // Mensaje específico cuando el único problema es que el grupo ya existe en el periodo
    $grupoRepetido = $errors->count() === 1 && str_contains((string) $errors->first('letra'), 'ya está registrado');
@endphp

@section('titulo', 'Nuevo grupo')
@section('titulo-errores', 'No se pudo registrar el grupo')
@section('intro-errores', $grupoRepetido ? 'La combinación de semestre y letra debe ser única dentro del periodo y ya existe:' : 'Revisa y corrige lo siguiente:')

@section('contenido')
<form method="POST" action="{{ route('admin.grupos.store') }}" class="formulario-tarjeta" novalidate
      x-data="{
          semestre: @js((string) old('semestre', '')),
          letra: @js((string) old('letra', '')),
          especialidad: @js((string) old('id_especialidad', '')),
          requiereEspecialidad() { return Number(this.semestre) >= 5; },
          nombre() { return this.semestre && this.letra ? this.semestre + '°' + this.letra : ''; }
      }">
    @csrf
    <h1>Nuevo grupo</h1>

    @include('admin.grupos._form', ['modo' => 'crear'])

    <div class="nota-vacia" x-text="nombre() ? 'Nombre del grupo: ' + nombre() : 'Elige semestre y letra para ver el nombre del grupo.'">Elige semestre y letra para ver el nombre del grupo.</div>

    <p class="ayuda">No puede repetirse la combinación de semestre y letra dentro del mismo periodo. Los grupos de 5.º y 6.º semestre llevan especialidad.</p>
    <p class="ayuda">Registrar el grupo no inscribe alumnos: se asignan después desde el detalle del grupo.</p>

    <div class="formulario-acciones">
        <a href="{{ route('admin.grupos.index') }}" class="btn btn-borde">Cancelar</a>
        <button type="submit" class="btn btn-primario">Registrar grupo</button>
    </div>
</form>
@endsection
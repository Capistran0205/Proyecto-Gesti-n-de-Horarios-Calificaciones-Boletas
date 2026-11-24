@extends('layouts.panel')

@section('titulo', 'Nueva materia')
@section('titulo-errores', 'No se pudo registrar la materia')

@section('contenido')
<form method="POST" action="{{ route('admin.materias.store') }}" class="formulario-tarjeta" novalidate
      x-data="{
          tipo: @js((string) old('tipo', '')),
          especialidad: @js((string) old('id_especialidad', '')),
          init() { this.$watch('tipo', valor => { if (valor !== '{{ \App\Models\Materia::TIPO_ESPECIALIDAD }}') this.especialidad = ''; }); }
      }">
    @csrf
    <h1>Nueva materia</h1>

    @include('admin.materias._form')

    <div class="formulario-acciones">
        <a href="{{ route('admin.materias.index') }}" class="btn btn-borde">Cancelar</a>
        <button type="submit" class="btn btn-primario">Registrar</button>
    </div>
</form>
@endsection

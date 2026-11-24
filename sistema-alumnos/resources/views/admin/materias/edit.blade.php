@extends('layouts.panel')

@section('titulo', 'Modificar materia')
@section('titulo-errores', 'No se pudieron guardar los cambios')

@section('contenido')
<form method="POST" action="{{ route('admin.materias.update', $materia) }}" class="formulario-tarjeta" novalidate
      x-data="{
          tipo: @js((string) old('tipo', $materia->tipo)),
          especialidad: @js((string) old('id_especialidad', $materia->id_especialidad)),
          init() { this.$watch('tipo', valor => { if (valor !== '{{ \App\Models\Materia::TIPO_ESPECIALIDAD }}') this.especialidad = ''; }); }
      }">
    @csrf
    @method('PUT')
    <h1>Modificar materia</h1>

    @include('admin.materias._form')

    <div class="meta">
        Registrado: {{ $materia->fecha_registro->format('d/m/Y H:i') }}<br>
        Última modificación: {{ $materia->fecha_modificacion->format('d/m/Y H:i') }}
    </div>

    <div class="formulario-acciones">
        <a href="{{ route('admin.materias.index') }}" class="btn btn-borde">Cancelar</a>
        <button type="submit" class="btn btn-primario">Guardar cambios</button>
    </div>
</form>
@endsection

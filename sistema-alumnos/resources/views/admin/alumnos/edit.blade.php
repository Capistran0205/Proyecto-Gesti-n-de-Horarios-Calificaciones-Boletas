@extends('layouts.panel')

@section('titulo', 'Modificar alumno')
@section('titulo-errores', 'No se pudieron guardar los cambios')

@section('contenido')
<form method="POST" action="{{ route('admin.alumnos.update', $alumno) }}" class="formulario-tarjeta" novalidate
      x-data="{
          semestre: @js((string) old('semestre', $alumno->alumno->semestre)),
          especialidad: @js((string) old('id_especialidad', $alumno->alumno->id_especialidad ?? '')),
          requiereEspecialidad() { return Number(this.semestre) >= 5; }
      }">
    @csrf
    @method('PUT')
    <h1>Modificar alumno</h1>

    @include('admin.alumnos._form', ['modo' => 'editar'])

    <p class="ayuda">El correo y la CURP deben ser únicos: no pueden repetirse con los de otro alumno. El área de especialidad se asigna desde 5.º semestre.</p>

    @if ($grupoVigente)
        <div class="nota-vacia">Está en el grupo {{ $grupoVigente->nombre }}: el semestre no se puede cambiar sin sacarlo del grupo.</div>
    @endif
    <div class="nota-vacia">La contraseña no se modifica aquí: el propio alumno la restablece desde el apartado correspondiente del sistema.</div>

    <div class="meta">
        Registrado: {{ $alumno->fecha_registro->format('d/m/Y H:i') }}<br>
        Última modificación: {{ $alumno->fecha_modificacion->format('d/m/Y H:i') }}
    </div>

    <div class="formulario-acciones">
        <a href="{{ route('admin.alumnos.index') }}" class="btn btn-borde">Cancelar</a>
        <button type="submit" class="btn btn-primario">Guardar cambios</button>
    </div>
</form>
@endsection

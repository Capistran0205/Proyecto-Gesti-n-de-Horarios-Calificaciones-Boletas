@extends('layouts.panel')

@section('titulo', 'Nuevo alumno')
@section('titulo-errores', 'No se pudo registrar al alumno')

@section('contenido')
<form method="POST" action="{{ route('admin.alumnos.store') }}" class="formulario-tarjeta" novalidate
      x-data="{
          semestre: @js((string) old('semestre', '')),
          especialidad: @js((string) old('id_especialidad', '')),
          contrasena: @js($contrasenaTemporal),
          requiereEspecialidad() { return Number(this.semestre) >= 5; },
          async generarOtra() {
              try {
                  const respuesta = await fetch(@js(route('admin.alumnos.contrasena')), { headers: { 'Accept': 'application/json' } });
                  this.contrasena = (await respuesta.json()).contrasena;
              } catch (error) {
                  // Si falla la consulta se conserva la contraseña actual
              }
          }
      }">
    @csrf
    <h1>Nuevo alumno</h1>

    @include('admin.alumnos._form', ['modo' => 'crear'])

    <p class="ayuda">La matrícula, el correo y la CURP deben ser únicos: no pueden repetirse con los de otro alumno. El área de especialidad se asigna desde 5.º semestre.</p>
    <p class="ayuda">La matrícula será su acceso con la contraseña temporal y no se podrá modificar. Registrar al alumno no lo inscribe a un grupo.</p>

    <div class="formulario-acciones">
        <a href="{{ route('admin.alumnos.index') }}" class="btn btn-borde">Cancelar</a>
        <button type="submit" class="btn btn-primario">Registrar</button>
    </div>
</form>
@endsection

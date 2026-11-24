@extends('layouts.panel')

@section('titulo', 'Nuevo administrador')
@section('titulo-errores', 'No se pudo registrar al administrador')

@section('contenido')
<form method="POST" action="{{ route('superusuario.administradores.store') }}" class="formulario-tarjeta" novalidate
      x-data="{
          contrasena: @js($contrasenaTemporal),
          async generarOtra() {
              try {
                  const respuesta = await fetch(@js(route('superusuario.administradores.contrasena')), { headers: { 'Accept': 'application/json' } });
                  this.contrasena = (await respuesta.json()).contrasena;
              } catch (error) {
                  // Si falla la consulta se conserva la contraseña actual
              }
          }
      }">
    @csrf
    <h1>Nuevo administrador</h1>

    @include('superusuario.administradores._form', ['modo' => 'crear'])

    <div class="campo-grupo">
        <label class="etiqueta" for="contrasena">Contraseña temporal *</label>
        <div class="campo-con-boton">
            <input class="entrada @error('contrasena') invalido @enderror" type="text" id="contrasena" name="contrasena" minlength="8"
                   x-model="contrasena" placeholder="Mínimo 8 caracteres" autocomplete="off" spellcheck="false">
            <button type="button" class="btn btn-borde" @click="generarOtra()">Generar otra</button>
        </div>
        <p class="ayuda">Entrégasela directamente al administrador. Deberá cambiarla en su primer ingreso.</p>
    </div>

    <p class="ayuda">Se creará su cuenta de acceso con el número de empleado y la contraseña temporal indicada. El número de empleado, el correo, la CURP y el RFC deben ser únicos, incluso frente a administradores dados de baja.</p>

    <div class="formulario-acciones">
        <a href="{{ route('superusuario.administradores.index') }}" class="btn btn-borde">Cancelar</a>
        <button type="submit" class="btn btn-primario">Registrar</button>
    </div>
</form>
@endsection

@extends('layouts.panel')

@section('titulo', 'Nuevo docente')
@section('titulo-errores', 'No se pudo registrar al docente')

@php
    // Estado inicial: lo reenviado si el formulario falló; si no, una fila de estudio vacía y todos los días con el horario escolar
    $estudiosIniciales = collect(old('estudios', []))
        ->map(fn ($e) => ['grado' => (string) ($e['grado_estudio'] ?? ''), 'descripcion' => $e['descripcion'] ?? ''])->values()->all();

    $bloquesHorario = config('horario.bloques');
    $inicioEscolar = $bloquesHorario[array_key_first($bloquesHorario)]['inicio'];
    $finEscolar = $bloquesHorario[array_key_last($bloquesHorario)]['fin'];
    $reenviada = old('disponibilidad');

    $rangosIniciales = [];
    foreach (array_keys(config('horario.dias')) as $dia) {
        $rangosIniciales[$dia] = is_array($reenviada)
            ? [
                'activo' => filter_var($reenviada[$dia]['activo'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'desde' => $reenviada[$dia]['desde'] ?? $inicioEscolar,
                'hasta' => $reenviada[$dia]['hasta'] ?? $finEscolar,
            ]
            : ['activo' => true, 'desde' => $inicioEscolar, 'hasta' => $finEscolar];
    }
    $definida = is_array($reenviada) && collect($rangosIniciales)->contains('activo', true);
@endphp

@section('contenido')
@include('admin.docentes._scripts')

<form method="POST" action="{{ route('admin.docentes.store') }}" class="formulario-tarjeta" novalidate
      :class="{ 'tarjeta-media': paso === 'disponibilidad' }"
      x-data="{
          ...estudiosDocente(@js($estudiosIniciales), 4),
          ...disponibilidadDocente(@js($rangosIniciales)),
          definida: @js($definida),
          paso: 'formulario',
          contrasena: @js($contrasenaTemporal),
          habilitado() { return this.definida || this.paso === 'disponibilidad'; },
          abrirDisponibilidad() { this.copiar(); this.paso = 'disponibilidad'; },
          cancelarDisponibilidad() { this.restaurar(); this.paso = 'formulario'; },
          guardarDisponibilidad() { if (this.validarDisp()) { this.definida = true; this.paso = 'formulario'; } },
          async generarOtra() {
              try {
                  const respuesta = await fetch(@js(route('admin.docentes.contrasena')), { headers: { 'Accept': 'application/json' } });
                  this.contrasena = (await respuesta.json()).contrasena;
              } catch (error) {
                  // Si falla la consulta se conserva la contraseña actual
              }
          }
      }">
    @csrf

    {{-- Paso 1: datos del docente --}}
    <div x-show="paso === 'formulario'">
        <h1>Nuevo docente</h1>

        @include('admin.docentes._form', ['modo' => 'crear'])

        <p class="ayuda">Se creará su cuenta de acceso con el número de empleado y la contraseña temporal indicada. El número de empleado, el correo, la CURP y el RFC deben ser únicos.</p>

        <div class="formulario-acciones">
            <a href="{{ route('admin.docentes.index') }}" class="btn btn-borde">Cancelar</a>
            <button type="submit" class="btn btn-primario">Registrar</button>
        </div>
    </div>

    {{-- Paso 2: disponibilidad semanal (se queda en el mismo formulario; sus campos se envían con el alta) --}}
    <div x-show="paso === 'disponibilidad'" x-cloak @keydown.enter.prevent>
        <h1>Asignar Disponibilidad Semanal</h1>
        <p class="subtitulo">Nuevo docente · Marca los días en que labora (lunes a viernes) y su horario de cada día.</p>

        @include('admin.docentes._cuadricula-disponibilidad')

        <div class="formulario-acciones">
            <button type="button" class="btn btn-borde" @click="cancelarDisponibilidad()">Cancelar</button>
            <button type="button" class="btn btn-primario" @click="guardarDisponibilidad()">Guardar disponibilidad</button>
        </div>
    </div>
</form>
@endsection

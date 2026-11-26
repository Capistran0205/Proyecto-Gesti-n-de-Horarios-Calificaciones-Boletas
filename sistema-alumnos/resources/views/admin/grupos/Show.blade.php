@extends('layouts.panel')

@php
    $porcentaje = $grupo->capacidad_max > 0 ? min(100, (int) round($grupo->alumnos_count / $grupo->capacidad_max * 100)) : 0;
@endphp

@section('titulo', 'Grupo ' . $grupo->nombre_grupo)

@section('contenido')
<div class="pagina" x-data="{ modal: null, nombre: '', matricula: '', accion: '' }" @keydown.escape.window="modal = null">
    <div class="pagina-encabezado">
        <div>
            <p class="migas">Panel Administrador › <a href="{{ route('admin.grupos.index', ['periodo' => $grupo->id_periodo]) }}">Grupos</a> › {{ $grupo->nombre_grupo }}</p>
            <h1>Grupo {{ $grupo->nombre_grupo }}</h1>
        </div>
        <div class="botones-fila">
            <a href="{{ route('admin.grupos.edit', $grupo) }}" class="btn btn-borde">Editar grupo</a>
            @if ($permiteAsignar)
                <a href="{{ route('admin.grupos.alumnos.create', $grupo) }}" class="btn btn-primario">Asignar alumnos</a>
            @else
                <span class="btn btn-gris">Asignar alumnos</span>
            @endif
        </div>
    </div>

    @unless ($permiteAsignar)
        <div class="aviso advertencia">El periodo {{ $grupo->periodo->nombre }} ya terminó: no se pueden asignar ni quitar alumnos de este grupo.</div>
    @endunless

    <div class="resumen-grupo">
        <dl class="resumen-datos">
            <div><dt>Periodo</dt><dd>{{ $grupo->periodo->nombre }}</dd></div>
            <div><dt>Semestre</dt><dd>{{ $grupo->semestre }}</dd></div>
            <div><dt>Especialidad</dt><dd>{{ $grupo->especialidad->descripcion ?? '—' }}</dd></div>
            <div><dt>Capacidad máxima</dt><dd>{{ $grupo->capacidad_max }} alumnos</dd></div>
        </dl>
        <div class="barra-cupo-texto">
            <strong>{{ $grupo->alumnos_count }} de {{ $grupo->capacidad_max }} alumnos inscritos</strong>
            <span>{{ $grupo->lugaresDisponibles() }} lugares disponibles</span>
        </div>
        <div class="barra-cupo" role="progressbar" aria-valuenow="{{ $grupo->alumnos_count }}" aria-valuemin="0" aria-valuemax="{{ $grupo->capacidad_max }}">
            <span style="width: {{ $porcentaje }}%"></span>
        </div>
    </div>

    <h2 class="titulo-seccion">Alumnos inscritos</h2>

    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Matrícula</th>
                    <th>Nombre completo</th>
                    <th>Correo</th>
                    @if ($permiteAsignar)
                        <th>Acciones</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($alumnos as $usuario)
                    <tr>
                        <td class="sin-salto">{{ $usuario->usuario }}</td>
                        <td>{{ $usuario->nombre_completo }}</td>
                        <td>{{ $usuario->correo_electronico }}</td>
                        @if ($permiteAsignar)
                            <td>
                                <button type="button" class="btn btn-mini baja"
                                        @click="modal = 'quitar'; nombre = @js($usuario->nombre_completo); matricula = @js($usuario->usuario); accion = @js(route('admin.grupos.alumnos.destroy', [$grupo, $usuario->id]))">Quitar del grupo</button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $permiteAsignar ? 4 : 3 }}" class="vacio">Este grupo aún no tiene alumnos inscritos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pie-tabla">
        <span>Mostrando {{ $alumnos->count() }} de {{ $alumnos->count() }} alumnos, ordenados por apellido.</span>
    </div>

    {{-- Confirmación para quitar a un alumno --}}
    <div class="modal-fondo" x-show="modal === 'quitar'" x-cloak role="dialog" aria-modal="true">
        <div class="modal" @click.outside="modal = null">
            <h2>Quitar alumno del grupo</h2>
            <p>Vas a quitar del grupo {{ $grupo->nombre_grupo }} a <strong x-text="nombre"></strong> (<span x-text="matricula"></span>).</p>
            <div class="aviso advertencia">Solo se quita su asignación al grupo. El alumno no se elimina ni se da de baja, y podrá asignarse a otro grupo de este periodo.</div>
            <form method="POST" :action="accion" class="modal-acciones">
                @csrf
                @method('DELETE')
                <button type="button" class="btn btn-borde" @click="modal = null">Cancelar</button>
                <button type="submit" class="btn btn-peligro">Quitar del grupo</button>
            </form>
        </div>
    </div>
</div>
@endsection
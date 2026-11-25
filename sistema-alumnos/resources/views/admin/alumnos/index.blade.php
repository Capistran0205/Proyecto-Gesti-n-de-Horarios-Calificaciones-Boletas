@extends('layouts.panel')

@section('titulo', 'Alumnos')
@section('titulo-exito', ($contrasenaEntregada ?? null) ? 'Alumno registrado' : 'Operación exitosa')
@section('titulo-errores', 'No se pudo completar la operación')

@section('contenido')
<div class="pagina" @keydown.escape.window="modal = null"
     x-data="{
         modal: null,
         nombre: '',
         matricula: '',
         grupo: '',
         accion: '',
         semestre: '',
         especialidad: '',
         requiereEspecialidad() { return Number(this.semestre) >= 5; }
     }">
    <div class="pagina-encabezado">
        <div>
            <p class="migas">Panel Administrador › Alumnos</p>
            <h1>Alumnos</h1>
        </div>
        <a href="{{ route('admin.alumnos.create') }}" class="btn btn-primario">+ Nuevo alumno</a>
    </div>

    <form method="GET" action="{{ route('admin.alumnos.index') }}" class="filtros">
        <div class="grupo crece">
            <label class="etiqueta" for="buscar">Buscar</label>
            <input class="entrada" type="search" id="buscar" name="buscar" value="{{ $buscar }}" placeholder="Matrícula o nombre">
        </div>
        <div class="grupo medio">
            <label class="etiqueta" for="semestre">Semestre</label>
            <select class="entrada" id="semestre" name="semestre">
                <option value="todos" @selected($semestre === 'todos')>Todos</option>
                @for ($numero = \App\Models\Alumno::SEMESTRE_MINIMO; $numero <= \App\Models\Alumno::SEMESTRE_MAXIMO; $numero++)
                    <option value="{{ $numero }}" @selected($semestre === (string) $numero)>{{ $numero }}</option>
                @endfor
            </select>
        </div>
        <div class="grupo medio">
            <label class="etiqueta" for="grupo">Grupo</label>
            <select class="entrada" id="grupo" name="grupo">
                <option value="todos" @selected($grupo === 'todos')>Todos</option>
                @foreach ($grupos as $grupoVigente)
                    <option value="{{ $grupoVigente->id }}" @selected($grupo === (string) $grupoVigente->id)>{{ $grupoVigente->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="grupo medio">
            <label class="etiqueta" for="estado">Estado</label>
            <select class="entrada" id="estado" name="estado">
                <option value="todos" @selected($estado === 'todos')>Todos</option>
                <option value="1" @selected($estado === '1')>Activo</option>
                <option value="0" @selected($estado === '0')>De baja</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primario">Buscar</button>
        <a href="{{ route('admin.alumnos.index') }}" class="btn btn-borde">Limpiar</a>
    </form>

    <div class="tabla-contenedor">
        <table class="tabla tabla-ancha">
            <thead>
                <tr>
                    <th>Matrícula</th>
                    <th>Nombre completo</th>
                    <th>CURP</th>
                    <th>Correo</th>
                    <th>Sem.</th>
                    <th>Área</th>
                    <th>Grupo</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($alumnos as $usuario)
                    @php $grupoDelAlumno = $gruposDeAlumnos->get($usuario->id); @endphp
                    <tr>
                        <td class="sin-salto">{{ $usuario->usuario }}</td>
                        <td class="sin-salto">{{ $usuario->nombre_completo }}</td>
                        <td class="sin-salto">{{ $usuario->alumno->curp }}</td>
                        <td>{{ $usuario->correo_electronico }}</td>
                        <td>{{ $usuario->alumno->semestre }}</td>
                        <td>{{ $usuario->alumno->especialidad->descripcion ?? '—' }}</td>
                        <td class="sin-salto">
                            @if ($grupoDelAlumno)
                                {{ $grupoDelAlumno->nombre }}
                            @else
                                <span class="tenue">Sin grupo</span>
                            @endif
                        </td>
                        <td>
                            @if ($usuario->estado === \App\Models\User::ESTADO_ACTIVO)
                                <span class="insignia activo">Activo</span>
                            @else
                                <span class="insignia baja">De baja</span>
                            @endif
                        </td>
                        <td>
                            <div class="acciones">
                                @if ($usuario->estado === \App\Models\User::ESTADO_ACTIVO)
                                    <a href="{{ route('admin.alumnos.edit', $usuario) }}" class="btn btn-mini editar">Editar</a>
                                    <button type="button" class="btn btn-mini baja"
                                            @click="modal = 'baja'; nombre = @js($usuario->nombre_completo); matricula = @js($usuario->usuario); grupo = @js($grupoDelAlumno->nombre ?? ''); accion = @js(route('admin.alumnos.baja', $usuario))">Dar de baja</button>
                                @else
                                    <button type="button" class="btn btn-mini reactivar"
                                            @click="modal = 'reactivar'; nombre = @js($usuario->nombre_completo); matricula = @js($usuario->usuario); semestre = @js((string) $usuario->alumno->semestre); especialidad = @js((string) ($usuario->alumno->id_especialidad ?? '')); accion = @js(route('admin.alumnos.reactivar', $usuario))">Reactivar</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="vacio">No se encontraron alumnos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pie-tabla">
        <span>Mostrando {{ $alumnos->count() }} de {{ $alumnos->total() }} alumnos</span>
        <x-paginacion :paginador="$alumnos" />
    </div>

    {{-- Confirmación de baja --}}
    <div class="modal-fondo" x-show="modal === 'baja'" x-cloak role="dialog" aria-modal="true">
        <div class="modal" @click.outside="modal = null">
            <h2>Dar de baja alumno</h2>
            <p>Vas a dar de baja a <strong x-text="nombre"></strong> (<span x-text="matricula"></span>).</p>
            <div class="aviso advertencia">
                El alumno perderá acceso<span x-show="grupo"> y saldrá de su grupo (<span x-text="grupo"></span>), liberando el cupo</span>.
                Conserva su historial y calificaciones.
            </div>
            <p class="nota-pequena">La baja es lógica: no se elimina ningún registro y se puede reactivar.</p>
            <form method="POST" :action="accion" class="modal-acciones">
                @csrf
                @method('PATCH')
                <button type="button" class="btn btn-borde" @click="modal = null">Cancelar</button>
                <button type="submit" class="btn btn-peligro">Confirmar baja</button>
            </form>
        </div>
    </div>

    {{-- Reactivación: el semestre no se actualiza solo, se confirma o corrige aquí --}}
    <div class="modal-fondo" x-show="modal === 'reactivar'" x-cloak role="dialog" aria-modal="true">
        <div class="modal" @click.outside="modal = null">
            <h2>Reactivar alumno</h2>
            <p>Vas a reactivar a <strong x-text="nombre"></strong> (<span x-text="matricula"></span>).</p>
            <div class="aviso exito">El alumno recupera su acceso y conserva su historial y calificaciones. Quedará sin grupo: la asignación se hace después.</div>
            <form method="POST" :action="accion">
                @csrf
                @method('PATCH')
                <div class="campo-grupo">
                    <label class="etiqueta" for="reactivar_semestre">Semestre actual *</label>
                    <select class="entrada" id="reactivar_semestre" name="semestre" x-model="semestre" @change="if (!requiereEspecialidad()) especialidad = ''">
                        @for ($numero = \App\Models\Alumno::SEMESTRE_MINIMO; $numero <= \App\Models\Alumno::SEMESTRE_MAXIMO; $numero++)
                            <option value="{{ $numero }}">{{ $numero }}</option>
                        @endfor
                    </select>
                </div>
                <div class="campo-grupo" x-show="requiereEspecialidad()">
                    <label class="etiqueta" for="reactivar_especialidad">Área de especialidad *</label>
                    <select class="entrada" id="reactivar_especialidad" name="id_especialidad" x-model="especialidad" :disabled="!requiereEspecialidad()">
                        <option value="">Selecciona</option>
                        @foreach ($especialidades as $especialidad)
                            <option value="{{ $especialidad->id }}">{{ $especialidad->descripcion }}</option>
                        @endforeach
                    </select>
                </div>
                <p class="ayuda">El semestre no se actualiza solo: confírmalo o corrígelo antes de reactivar. Desde 5.º semestre es necesario asignar el área.</p>
                <div class="modal-acciones">
                    <button type="button" class="btn btn-borde" @click="modal = null">Cancelar</button>
                    <button type="submit" class="btn btn-exito">Confirmar reactivación</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

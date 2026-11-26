@extends('layouts.panel')

@section('titulo', 'Materias')
@section('titulo-errores', 'No se pudo completar la operación')

@section('contenido')
<div class="pagina" x-data="{ modal: null, nombre: '', clave: '', accion: '' }" @keydown.escape.window="modal = null">
    <div class="pagina-encabezado">
        <div>
            <p class="migas">Panel Administrador › Materias</p>
            <h1>Materias</h1>
        </div>
        <div class="encabezado-acciones">
            <x-boton-regresar />
            <a href="{{ route('admin.materias.create') }}" class="btn btn-primario">+ Nueva materia</a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.materias.index') }}" class="filtros">
        <div class="grupo crece">
            <label class="etiqueta" for="buscar">Buscar</label>
            <input class="entrada" type="search" id="buscar" name="buscar" value="{{ $buscar }}" placeholder="Clave o nombre">
        </div>
        <div class="grupo medio">
            <label class="etiqueta" for="semestre">Semestre</label>
            <select class="entrada" id="semestre" name="semestre">
                <option value="todos" @selected($semestre === 'todos')>Todos</option>
                @foreach (range(1, 6) as $numero)
                    <option value="{{ $numero }}" @selected($semestre === (string) $numero)>{{ $numero }}</option>
                @endforeach
            </select>
        </div>
        <div class="grupo medio-ancho">
            <label class="etiqueta" for="tipo">Tipo</label>
            <select class="entrada" id="tipo" name="tipo">
                <option value="todos" @selected($tipo === 'todos')>Todos</option>
                @foreach ($tipos as $valor => $texto)
                    <option value="{{ $valor }}" @selected($tipo === (string) $valor)>{{ $texto }}</option>
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
        <a href="{{ route('admin.materias.index') }}" class="btn btn-borde">Limpiar</a>
    </form>

    <div class="tabla-contenedor">
        <table class="tabla tabla-ancha">
            <thead>
                <tr>
                    <th>Clave</th>
                    <th>Nombre</th>
                    <th>Semestre</th>
                    <th>Tipo</th>
                    <th>Especialidad</th>
                    <th>Horas/sem.</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($materias as $materia)
                    <tr>
                        <td class="sin-salto">{{ $materia->clave }}</td>
                        <td>{{ $materia->nombre }}</td>
                        <td class="sin-salto">{{ $materia->semestre }}</td>
                        <td class="sin-salto">{{ $materia->tipo_texto }}</td>
                        <td>{{ $materia->especialidad->descripcion ?? '—' }}</td>
                        <td class="sin-salto">{{ $materia->horas_semana }}</td>
                        <td class="sin-salto">
                            @if ($materia->estado === \App\Models\Materia::ESTADO_ACTIVA)
                                <span class="insignia activo">Activo</span>
                            @else
                                <span class="insignia baja">De baja</span>
                            @endif
                        </td>
                        <td class="sin-salto">
                            <div class="acciones">
                                @if ($materia->estado === \App\Models\Materia::ESTADO_ACTIVA)
                                    <a href="{{ route('admin.materias.edit', $materia) }}" class="btn btn-mini editar">Editar</a>
                                    <button type="button" class="btn btn-mini baja"
                                            @click="modal = 'baja'; nombre = @js($materia->nombre); clave = @js($materia->clave); accion = @js(route('admin.materias.baja', $materia))">Dar de baja</button>
                                @else
                                    <button type="button" class="btn btn-mini reactivar"
                                            @click="modal = 'reactivar'; nombre = @js($materia->nombre); clave = @js($materia->clave); accion = @js(route('admin.materias.reactivar', $materia))">Reactivar</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="vacio">No se encontraron materias.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pie-tabla">
        <span>Mostrando {{ $materias->count() }} de {{ $materias->total() }} materias</span>
        <x-paginacion :paginador="$materias" />
    </div>

    {{-- Confirmación de baja --}}
    <div class="modal-fondo" x-show="modal === 'baja'" x-cloak role="dialog" aria-modal="true">
        <div class="modal" @click.outside="modal = null">
            <h2>Dar de baja materia</h2>
            <p>Vas a dar de baja la materia <strong x-text="nombre"></strong> (<span x-text="clave"></span>).</p>
            <div class="aviso advertencia">Si la materia ya está asignada a un docente o a un grupo, esas asignaciones no cambian. A partir de ahora no podrá asignarse a nadie más.</div>
            <p class="nota-pequena">La baja es lógica: no se elimina ningún registro, se conserva el historial y se puede reactivar.</p>
            <form method="POST" :action="accion" class="modal-acciones">
                @csrf
                @method('PATCH')
                <button type="button" class="btn btn-borde" @click="modal = null">Cancelar</button>
                <button type="submit" class="btn btn-peligro">Confirmar baja</button>
            </form>
        </div>
    </div>

    {{-- Confirmación de reactivación --}}
    <div class="modal-fondo" x-show="modal === 'reactivar'" x-cloak role="dialog" aria-modal="true">
        <div class="modal" @click.outside="modal = null">
            <h2>Reactivar materia</h2>
            <p>Vas a reactivar la materia <strong x-text="nombre"></strong> (<span x-text="clave"></span>).</p>
            <div class="aviso exito">La materia vuelve a estar disponible para asignarse a docentes y grupos. Las asignaciones existentes no cambian.</div>
            <form method="POST" :action="accion" class="modal-acciones">
                @csrf
                @method('PATCH')
                <button type="button" class="btn btn-borde" @click="modal = null">Cancelar</button>
                <button type="submit" class="btn btn-exito">Confirmar reactivación</button>
            </form>
        </div>
    </div>
</div>
@endsection

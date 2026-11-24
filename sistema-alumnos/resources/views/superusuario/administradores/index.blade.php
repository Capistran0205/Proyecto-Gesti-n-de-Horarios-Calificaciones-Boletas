@extends('layouts.panel')

@section('titulo', 'Administradores')
@section('titulo-exito', ($contrasenaEntregada ?? null) ? 'Administrador registrado' : 'Operación exitosa')
@section('titulo-errores', 'No se pudo completar la operación')

@section('contenido')
<div class="pagina" x-data="{ modal: null, nombre: '', numero: '', accion: '' }" @keydown.escape.window="modal = null">
    <div class="pagina-encabezado">
        <div>
            <p class="migas">Panel SuperUsuario › Administración</p>
            <h1>Administradores</h1>
        </div>
        <a href="{{ route('superusuario.administradores.create') }}" class="btn btn-primario">+ Nuevo administrador</a>
    </div>

    <form method="GET" action="{{ route('superusuario.administradores.index') }}" class="filtros">
        <div class="grupo crece">
            <label class="etiqueta" for="buscar">Buscar</label>
            <input class="entrada" type="search" id="buscar" name="buscar" value="{{ $buscar }}" placeholder="Número de empleado o nombre">
        </div>
        <div class="grupo fijo">
            <label class="etiqueta" for="estado">Estado</label>
            <select class="entrada" id="estado" name="estado">
                <option value="todos" @selected($estado === 'todos')>Todos</option>
                <option value="1" @selected($estado === '1')>Activos</option>
                <option value="0" @selected($estado === '0')>De baja</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primario">Buscar</button>
        <a href="{{ route('superusuario.administradores.index') }}" class="btn btn-borde">Limpiar</a>
    </form>

    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>N.º empleado</th>
                    <th>Nombre completo</th>
                    <th>Correo</th>
                    <th>Estado</th>
                    <th>Última modificación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($administradores as $administrador)
                    <tr>
                        <td>{{ $administrador->usuario }}</td>
                        <td>{{ $administrador->nombre_completo }}</td>
                        <td>{{ $administrador->correo_electronico }}</td>
                        <td>
                            @if ($administrador->estado === \App\Models\User::ESTADO_ACTIVO)
                                <span class="insignia activo">Activo</span>
                            @else
                                <span class="insignia baja">De baja</span>
                            @endif
                        </td>
                        <td>{{ $administrador->fecha_modificacion->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="acciones">
                                @if ($administrador->estado === \App\Models\User::ESTADO_ACTIVO)
                                    <a href="{{ route('superusuario.administradores.edit', $administrador) }}" class="btn btn-mini editar">Editar</a>
                                    <button type="button" class="btn btn-mini baja"
                                            @click="modal = 'baja'; nombre = @js($administrador->nombre_completo); numero = @js($administrador->usuario); accion = @js(route('superusuario.administradores.baja', $administrador))">Dar de baja</button>
                                @else
                                    <button type="button" class="btn btn-mini reactivar"
                                            @click="modal = 'reactivar'; nombre = @js($administrador->nombre_completo); numero = @js($administrador->usuario); accion = @js(route('superusuario.administradores.reactivar', $administrador))">Reactivar</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="vacio">No se encontraron administradores.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pie-tabla">
        <span>Mostrando {{ $administradores->count() }} de {{ $administradores->total() }} administradores</span>
        @if ($administradores->hasPages())
            <nav class="paginacion" aria-label="Paginación">
                <a href="{{ $administradores->previousPageUrl() }}" class="btn btn-borde {{ $administradores->onFirstPage() ? 'inactivo' : '' }}">Anterior</a>
                <span>Página {{ $administradores->currentPage() }} de {{ $administradores->lastPage() }}</span>
                <a href="{{ $administradores->nextPageUrl() }}" class="btn btn-borde {{ $administradores->hasMorePages() ? '' : 'inactivo' }}">Siguiente</a>
            </nav>
        @endif
    </div>

    {{-- Confirmación de baja --}}
    <div class="modal-fondo" x-show="modal === 'baja'" x-cloak role="dialog" aria-modal="true">
        <div class="modal" @click.outside="modal = null">
            <h2>Dar de baja administrador</h2>
            <p>Vas a dar de baja a <strong x-text="nombre"></strong> (<span x-text="numero"></span>).</p>
            <div class="aviso advertencia">El administrador perderá su acceso al panel. Conserva su registro y puedes reactivarlo cuando lo necesites.</div>
            <p class="nota-pequena">La baja es lógica: no se elimina ningún registro y se puede reactivar.</p>
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
            <h2>Reactivar administrador</h2>
            <p>Vas a reactivar a <strong x-text="nombre"></strong> (<span x-text="numero"></span>).</p>
            <div class="aviso exito">El administrador recupera su acceso al panel con su número de empleado y su contraseña actual.</div>
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

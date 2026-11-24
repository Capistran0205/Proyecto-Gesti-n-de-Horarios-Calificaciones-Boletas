@extends('layouts.panel')

@section('titulo', 'Docentes')
@section('titulo-exito', ($contrasenaEntregada ?? null) ? 'Docente registrado' : 'Operación exitosa')
@section('titulo-errores', 'No se pudo completar la operación')

@php
    // Baja de un docente con clases en el periodo vigente: la ventana de baja se abre sola con la advertencia
    $advertencia = session('advertencia_baja');
@endphp

@section('contenido')
<div class="pagina" @keydown.escape.window="modal = null"
     x-data="{
         modal: @js($advertencia ? 'baja' : null),
         nombre: @js($advertencia['nombre'] ?? ''),
         numero: @js($advertencia['usuario'] ?? ''),
         curp: @js($advertencia['curp'] ?? ''),
         accion: @js($advertencia ? route('admin.docentes.baja', $advertencia['id']) : ''),
         aviso: @js($advertencia['mensaje'] ?? ''),
         confirmar: @js($advertencia !== null)
     }">
    <div class="pagina-encabezado">
        <div>
            <p class="migas">Panel Administrador › Docentes</p>
            <h1>Docentes</h1>
        </div>
        <a href="{{ route('admin.docentes.create') }}" class="btn btn-primario">+ Nuevo docente</a>
    </div>

    <form method="GET" action="{{ route('admin.docentes.index') }}" class="filtros">
        <div class="grupo crece">
            <label class="etiqueta" for="buscar">Buscar</label>
            <input class="entrada" type="search" id="buscar" name="buscar" value="{{ $buscar }}" placeholder="Número de empleado o nombre">
        </div>
        <div class="grupo fijo">
            <label class="etiqueta" for="area">Área</label>
            <select class="entrada" id="area" name="area">
                <option value="todos" @selected($area === 'todos')>Todas</option>
                @foreach ($areas as $areaDisciplinar)
                    <option value="{{ $areaDisciplinar->id }}" @selected($area === (string) $areaDisciplinar->id)>{{ $areaDisciplinar->descripcion }}</option>
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
        <a href="{{ route('admin.docentes.index') }}" class="btn btn-borde">Limpiar</a>
    </form>

    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Número de empleado</th>
                    <th>Nombre completo</th>
                    <th>CURP</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($docentes as $usuario)
                    <tr>
                        <td>{{ $usuario->usuario }}</td>
                        <td>{{ $usuario->nombre_completo }}</td>
                        <td>{{ $usuario->docente->curp }}</td>
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
                                    <a href="{{ route('admin.docentes.edit', $usuario) }}" class="btn btn-mini editar">Editar</a>
                                    <button type="button" class="btn btn-mini baja"
                                            @click="modal = 'baja'; aviso = ''; confirmar = false; nombre = @js($usuario->nombre_completo); numero = @js($usuario->usuario); curp = @js($usuario->docente->curp); accion = @js(route('admin.docentes.baja', $usuario))">Dar de baja</button>
                                @else
                                    <button type="button" class="btn btn-mini reactivar"
                                            @click="modal = 'reactivar'; nombre = @js($usuario->nombre_completo); numero = @js($usuario->usuario); curp = @js($usuario->docente->curp); accion = @js(route('admin.docentes.reactivar', $usuario))">Reactivar</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="vacio">No se encontraron docentes.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pie-tabla">
        <span>Mostrando {{ $docentes->count() }} de {{ $docentes->total() }} docentes</span>
        <x-paginacion :paginador="$docentes" />
    </div>

    {{-- Confirmación de baja (con advertencia si tiene clases en el periodo vigente) --}}
    <div class="modal-fondo" x-show="modal === 'baja'" x-cloak role="dialog" aria-modal="true">
        <div class="modal" @click.outside="modal = null">
            <h2>¿Dar de baja a <span x-text="nombre"></span>?</h2>
            <p>El docente ya no podrá ingresar al sistema. Sus datos, horarios y calificaciones registradas se conservan, y puedes reactivarlo cuando lo necesites.</p>
            <div class="aviso advertencia" x-show="aviso" x-text="aviso"></div>
            <dl class="ficha">
                <div><dt>Número de empleado</dt><dd x-text="numero"></dd></div>
                <div><dt>CURP</dt><dd x-text="curp"></dd></div>
            </dl>
            <form method="POST" :action="accion" class="modal-acciones">
                @csrf
                @method('PATCH')
                <input type="hidden" name="confirmar" :value="confirmar ? 1 : 0">
                <button type="button" class="btn btn-borde" @click="modal = null">Cancelar</button>
                <button type="submit" class="btn btn-peligro" x-text="aviso ? 'Confirmar baja' : 'Dar de baja'">Dar de baja</button>
            </form>
        </div>
    </div>

    {{-- Confirmación de reactivación --}}
    <div class="modal-fondo" x-show="modal === 'reactivar'" x-cloak role="dialog" aria-modal="true">
        <div class="modal" @click.outside="modal = null">
            <h2>¿Reactivar a <span x-text="nombre"></span>?</h2>
            <p>El docente podrá volver a ingresar al sistema con su número de empleado. Su información se conserva tal como estaba al darlo de baja.</p>
            <dl class="ficha">
                <div><dt>Número de empleado</dt><dd x-text="numero"></dd></div>
                <div><dt>CURP</dt><dd x-text="curp"></dd></div>
            </dl>
            <form method="POST" :action="accion" class="modal-acciones">
                @csrf
                @method('PATCH')
                <button type="button" class="btn btn-borde" @click="modal = null">Cancelar</button>
                <button type="submit" class="btn btn-exito">Reactivar</button>
            </form>
        </div>
    </div>
</div>
@endsection

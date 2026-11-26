@extends('layouts.panel')

@section('titulo', 'Grupos')
@section('titulo-errores', 'No se pudo completar la operación')

@section('contenido')
<div class="pagina">
    <div class="pagina-encabezado">
        <div>
            <p class="migas">Panel Administrador › Grupos</p>
            <h1>Grupos</h1>
        </div>
        <a href="{{ route('admin.grupos.create', ['periodo' => $idPeriodo]) }}" class="btn btn-primario">+ Nuevo grupo</a>
    </div>

    <form method="GET" action="{{ route('admin.grupos.index') }}" class="filtros">
        <div class="grupo crece">
            <label class="etiqueta" for="periodo">Periodo</label>
            <select class="entrada" id="periodo" name="periodo">
                @foreach ($periodos as $periodo)
                    <option value="{{ $periodo->id }}" @selected((int) $idPeriodo === (int) $periodo->id)>{{ $periodo->nombre }}{{ $periodoVigente?->id === $periodo->id ? ' (vigente)' : '' }}</option>
                @endforeach
            </select>
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
        <button type="submit" class="btn btn-primario">Buscar</button>
        <a href="{{ route('admin.grupos.index') }}" class="btn btn-borde">Limpiar</a>
    </form>

    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Grupo</th>
                    <th>Semestre</th>
                    <th>Especialidad</th>
                    <th>Alumnos</th>
                    <th>Lugares libres</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($grupos as $grupo)
                    <tr>
                        <td class="sin-salto"><strong>{{ $grupo->nombre_grupo }}</strong></td>
                        <td>{{ $grupo->semestre }}</td>
                        <td>{{ $grupo->especialidad->descripcion ?? '—' }}</td>
                        <td class="sin-salto">{{ $grupo->alumnos_count }} / {{ $grupo->capacidad_max }}</td>
                        <td>
                            @if ($grupo->estaLleno())
                                <span class="insignia lleno">Lleno</span>
                            @else
                                {{ $grupo->lugaresDisponibles() }}
                            @endif
                        </td>
                        <td>
                            <div class="acciones">
                                <a href="{{ route('admin.grupos.show', $grupo) }}" class="btn btn-mini editar">Ver</a>
                                <a href="{{ route('admin.grupos.edit', $grupo) }}" class="btn btn-mini editar">Editar</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="vacio">
                            @if ($periodos->isEmpty())
                                Aún no hay periodos escolares registrados: registra uno antes de crear grupos.
                            @else
                                No se encontraron grupos en este periodo.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pie-tabla">
        <span>Mostrando {{ $grupos->count() }} de {{ $grupos->total() }} grupos</span>
        <x-paginacion :paginador="$grupos" />
    </div>
</div>
@endsection
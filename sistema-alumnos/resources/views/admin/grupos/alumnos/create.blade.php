@extends('layouts.panel')

@php
    // El modal de error cambia de texto según el motivo (cupo agotado o alumno que dejó de ser elegible)
    $primerError = (string) $errors->first('alumnos');
    $introAsignacion = match (true) {
        str_starts_with($primerError, 'Lugares disponibles') => 'El grupo ya no tiene lugares suficientes. Es posible que otra persona haya asignado alumnos mientras capturabas. No se asignó a ningún alumno:',
        str_starts_with($primerError, 'La matrícula') => 'Alguno de los alumnos seleccionados ya no es elegible. No se asignó a ningún alumno:',
        default => 'Revisa y corrige lo siguiente:',
    };
    $lugares = $grupo->lugaresDisponibles();
@endphp

@section('titulo', 'Asignar alumnos')
@section('titulo-errores', 'No se pudo asignar a los alumnos')
@section('intro-errores', $introAsignacion)

@section('contenido')
<div class="pagina"
     x-data="{
         lugares: {{ $lugares }},
         seleccionados: @js(array_map('strval', (array) old('alumnos', []))),
         libres() { return Math.max(0, this.lugares - this.seleccionados.length); },
         lleno() { return this.seleccionados.length >= this.lugares; },
         excedido() { return this.seleccionados.length > this.lugares; }
     }">
    <div class="pagina-encabezado">
        <div>
            <p class="migas">Panel Administrador › <a href="{{ route('admin.grupos.index', ['periodo' => $grupo->id_periodo]) }}">Grupos</a> › <a href="{{ route('admin.grupos.show', $grupo) }}">{{ $grupo->nombre_grupo }}</a> › Asignar alumnos</p>
            <h1>Asignar alumnos al grupo {{ $grupo->nombre_grupo }}</h1>
        </div>
    </div>

    <div class="resumen-grupo">
        <div class="resumen-datos">
            <dl class="resumen-lista">
            <div><dt>Periodo</dt><dd>{{ $grupo->periodo->nombre }}</dd></div>
            <div><dt>Semestre</dt><dd>{{ $grupo->semestre }}</dd></div>
            <div><dt>Especialidad</dt><dd>{{ $grupo->especialidad->descripcion ?? '—' }}</dd></div>
            <div><dt>Inscritos</dt><dd>{{ $grupo->alumnos_count }} / {{ $grupo->capacidad_max }}</dd></div>
            </dl>
            <div class="contador-lugares empuja" :class="{ agotado: libres() === 0 }">
                <strong x-text="libres()">{{ $lugares }}</strong>
                <span><b>lugares libres</b> tras asignar a <span x-text="seleccionados.length">0</span> seleccionados</span>
            </div>
        </div>
    </div>

    <p class="ayuda ayuda-suelta">
        Solo aparecen alumnos activos de {{ $grupo->semestre }}.º semestre{{ $grupo->esDeEspecialidad() ? ' del área ' . $grupo->especialidad->descripcion : '' }} que aún no tienen grupo en este periodo.
    </p>

    <form method="GET" action="{{ route('admin.grupos.alumnos.create', $grupo) }}" class="filtros">
        <div class="grupo crece">
            <label class="etiqueta" for="buscar">Buscar</label>
            <input class="entrada" type="search" id="buscar" name="buscar" value="{{ $buscar }}" placeholder="Matrícula o nombre">
        </div>
        <button type="submit" class="btn btn-primario">Buscar</button>
        <a href="{{ route('admin.grupos.alumnos.create', $grupo) }}" class="btn btn-borde">Limpiar</a>
    </form>
    <p class="ayuda ayuda-suelta" x-show="seleccionados.length > 0" x-cloak>Al buscar se pierde la selección actual: asigna primero a los alumnos marcados.</p>

    <div class="aviso advertencia" x-show="excedido()" x-cloak>Hay más alumnos seleccionados que lugares disponibles: quita alguno para poder asignar.</div>
    <div class="aviso advertencia" x-show="lleno() && !excedido()" x-cloak>Alcanzaste el cupo del grupo: ya no se pueden seleccionar más alumnos.</div>

    <form method="POST" action="{{ route('admin.grupos.alumnos.store', $grupo) }}">
        @csrf

        <div class="tabla-contenedor">
            <table class="tabla tabla-seleccion">
                <thead>
                    <tr>
                        <th>Asignar</th>
                        <th>Matrícula</th>
                        <th>Nombre completo</th>
                        <th>Correo</th>
                        <th>Sem.</th>
                        <th>Área</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($alumnos as $usuario)
                        <tr>
                            <td>
                                <input type="checkbox" name="alumnos[]" value="{{ $usuario->id }}" x-model="seleccionados"
                                       :disabled="lleno() && !seleccionados.includes('{{ $usuario->id }}')"
                                       aria-label="Asignar a {{ $usuario->nombre_completo }}">
                            </td>
                            <td class="sin-salto">{{ $usuario->usuario }}</td>
                            <td>{{ $usuario->nombre_completo }}</td>
                            <td>{{ $usuario->correo_electronico }}</td>
                            <td>{{ $usuario->alumno->semestre }}</td>
                            <td>{{ $usuario->alumno->especialidad->descripcion ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="vacio">
                                {{ $buscar !== '' ? 'No se encontraron alumnos disponibles con esa búsqueda.' : 'No hay alumnos disponibles para este grupo.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pie-tabla">
            <span>Mostrando {{ $alumnos->count() }} alumnos disponibles</span>
            <div class="botones-fila">
                <a href="{{ route('admin.grupos.show', $grupo) }}" class="btn btn-borde">Cancelar</a>
                <button type="submit" class="btn btn-primario" :disabled="seleccionados.length === 0 || excedido()">
                    Asignar alumnos<span x-show="seleccionados.length > 0" x-cloak> (<span x-text="seleccionados.length"></span>)</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
{{-- Campos del alta y la edición de grupos. Variables: $modo ('crear' | 'editar'), $especialidades, $letras y
     - en el alta: $periodos, $periodoVigente, $idPeriodoSugerido y $capacidad;
     - al editar: $grupo (con periodo y alumnos_count) y $tieneAlumnos.
     El formulario que lo incluye declara el x-data con: semestre, letra, especialidad y requiereEspecialidad(). --}}
@php
    $bloqueado = $modo === 'editar' && $tieneAlumnos; // con alumnos inscritos, semestre y especialidad no cambian
@endphp

<div class="campo-grupo">
    @if ($modo === 'crear')
        <label class="etiqueta" for="id_periodo">Periodo *</label>
        <select class="entrada @error('id_periodo') invalido @enderror" id="id_periodo" name="id_periodo">
            <option value="">Selecciona</option>
            @foreach ($periodos as $periodo)
                <option value="{{ $periodo->id }}" @selected((string) old('id_periodo', $idPeriodoSugerido) === (string) $periodo->id)>{{ $periodo->nombre }}{{ $periodoVigente?->id === $periodo->id ? ' (vigente)' : '' }}</option>
            @endforeach
        </select>
    @else
        {{-- El periodo de un grupo nunca cambia --}}
        <label class="etiqueta" for="id_periodo">Periodo</label>
        <input class="entrada" type="text" id="id_periodo" value="{{ $grupo->periodo->nombre }}" disabled>
    @endif
</div>

<div class="fila">
    <div class="campo-grupo">
        <label class="etiqueta" for="semestre">Semestre *</label>
        {{-- Deshabilitado no se envía: con alumnos inscritos el servidor conserva el semestre guardado --}}
        <select class="entrada @error('semestre') invalido @enderror" id="semestre" name="semestre" x-model="semestre"
                @change="if (!requiereEspecialidad()) especialidad = ''" @disabled($bloqueado)>
            <option value="">Del 1 al 6</option>
            @for ($numero = \App\Models\Alumno::SEMESTRE_MINIMO; $numero <= \App\Models\Alumno::SEMESTRE_MAXIMO; $numero++)
                <option value="{{ $numero }}">{{ $numero }}</option>
            @endfor
        </select>
    </div>
    <div class="campo-grupo">
        <label class="etiqueta" for="letra">Letra *</label>
        <select class="entrada @error('letra') invalido @enderror" id="letra" name="letra" x-model="letra">
            <option value="">De la A a la Z</option>
            @foreach ($letras as $letra)
                <option value="{{ $letra }}">{{ $letra }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="campo-grupo">
    <label class="etiqueta" for="id_especialidad">Especialidad<span x-show="requiereEspecialidad()"> *</span></label>
    <select class="entrada @error('id_especialidad') invalido @enderror" id="id_especialidad" name="id_especialidad" x-model="especialidad"
            :disabled="!requiereEspecialidad() || @js($bloqueado)">
        <option value="" x-text="requiereEspecialidad() ? 'Selecciona' : 'Solo para grupos de 5.º y 6.º semestre'">Solo para grupos de 5.º y 6.º semestre</option>
        @foreach ($especialidades as $especialidad)
            <option value="{{ $especialidad->id }}">{{ $especialidad->descripcion }}</option>
        @endforeach
    </select>
</div>

{{-- La capacidad solo se muestra: la fija el sistema (40) y solo el SuperUsuario puede cambiarla --}}
@if ($modo === 'crear')
    <div class="campo-grupo">
        <label class="etiqueta" for="capacidad_max">Capacidad máxima</label>
        <input class="entrada" type="text" id="capacidad_max" value="{{ $capacidad }}" readonly>
        <p class="ayuda">Los grupos nuevos se crean con capacidad de {{ $capacidad }} alumnos. Solo el SuperUsuario puede ampliarla.</p>
    </div>
@else
    <div class="fila">
        <div class="campo-grupo">
            <label class="etiqueta" for="capacidad_max">Capacidad máxima</label>
            <input class="entrada" type="text" id="capacidad_max" value="{{ $grupo->capacidad_max }}" readonly>
        </div>
        <div class="campo-grupo">
            <label class="etiqueta" for="inscritos">Alumnos inscritos</label>
            <input class="entrada" type="text" id="inscritos" value="{{ $grupo->alumnos_count }}" readonly>
        </div>
    </div>
@endif
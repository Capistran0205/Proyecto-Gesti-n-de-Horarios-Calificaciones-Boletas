{{-- Campos compartidos por el alta y la edición. Variables: $areas, $especialidades, $tipos y, al editar, $materia.
     El formulario que lo incluye debe declarar x-data="{ tipo: ..., especialidad: ... }" para el campo condicional. --}}
@php
    $dato = fn (string $campo, $porDefecto = '') => old($campo, $porDefecto);
@endphp

<div class="campo-grupo">
    <label class="etiqueta" for="clave">Clave *</label>
    <input class="entrada campo-mayusculas @error('clave') invalido @enderror" type="text" id="clave" name="clave" maxlength="20"
           value="{{ $dato('clave', $materia->clave ?? '') }}" placeholder="Ej. MAT1" autocomplete="off" autofocus>
</div>

<div class="campo-grupo">
    <label class="etiqueta" for="nombre">Nombre *</label>
    <input class="entrada @error('nombre') invalido @enderror" type="text" id="nombre" name="nombre" maxlength="120"
           value="{{ $dato('nombre', $materia->nombre ?? '') }}" placeholder="Ej. Matemáticas I" autocomplete="off">
</div>

<div class="fila fila-desigual">
    <div class="campo-grupo">
        <label class="etiqueta" for="semestre">Semestre *</label>
        <select class="entrada @error('semestre') invalido @enderror" id="semestre" name="semestre">
            <option value="">Del 1 al 6</option>
            @foreach (range(1, 6) as $numero)
                <option value="{{ $numero }}" @selected((string) $dato('semestre', $materia->semestre ?? '') === (string) $numero)>{{ $numero }}</option>
            @endforeach
        </select>
    </div>
    <div class="campo-grupo">
        <label class="etiqueta" for="tipo">Tipo *</label>
        <select class="entrada @error('tipo') invalido @enderror" id="tipo" name="tipo" x-model="tipo">
            <option value="">Tronco común o especialidad</option>
            @foreach ($tipos as $valor => $texto)
                <option value="{{ $valor }}">{{ $texto }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="campo-grupo">
    <label class="etiqueta" for="id_area_disciplinar">Área disciplinar *</label>
    <select class="entrada @error('id_area_disciplinar') invalido @enderror" id="id_area_disciplinar" name="id_area_disciplinar">
        <option value="">Selecciona el área disciplinar</option>
        @foreach ($areas as $area)
            <option value="{{ $area->id }}" @selected((string) $dato('id_area_disciplinar', $materia->id_area_disciplinar ?? '') === (string) $area->id)>{{ $area->descripcion }}</option>
        @endforeach
    </select>
</div>

{{-- La especialidad solo aplica (y es obligatoria) cuando el tipo es "Especialidad" --}}
<div class="campo-grupo">
    <label class="etiqueta" for="id_especialidad">Especialidad<span x-text="tipo === '{{ \App\Models\Materia::TIPO_ESPECIALIDAD }}' ? ' *' : ''"></span></label>
    <select class="entrada @error('id_especialidad') invalido @enderror" id="id_especialidad" name="id_especialidad"
            x-model="especialidad" :disabled="tipo !== '{{ \App\Models\Materia::TIPO_ESPECIALIDAD }}'">
        <option value="" x-text="tipo === '{{ \App\Models\Materia::TIPO_ESPECIALIDAD }}' ? 'Selecciona la especialidad' : 'Solo para materias de especialidad'">Solo para materias de especialidad</option>
        @foreach ($especialidades as $especialidad)
            <option value="{{ $especialidad->id }}">{{ $especialidad->descripcion }}</option>
        @endforeach
    </select>
</div>

<div class="campo-grupo">
    <label class="etiqueta" for="horas_semana">Horas semanales *</label>
    <input class="entrada @error('horas_semana') invalido @enderror" type="number" id="horas_semana" name="horas_semana" min="1" max="10" step="1" inputmode="numeric"
           value="{{ $dato('horas_semana', $materia->horas_semana ?? '') }}" placeholder="Del 1 al 10">
</div>

<p class="ayuda">La clave es única. El nombre no puede repetirse dentro del mismo semestre.</p>

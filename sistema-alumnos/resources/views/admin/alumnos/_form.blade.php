{{-- Campos del alta y la edición de alumnos. Variables: $modo ('crear' | 'editar'), $especialidades y, al editar,
     $alumno (usuario con alumno) y $grupoVigente.
     El formulario que lo incluye declara el x-data con: semestre, especialidad, requiereEspecialidad() y, en el alta,
     contrasena y generarOtra(). --}}
@php
    $dato = fn (string $campo, $porDefecto = '') => old($campo, $porDefecto);
    $filtroNombre = "this.value = this.value.replace(/[^\\p{L}\\p{M} ]/gu, '')";
    $bloqueado = $modo === 'editar' && ($grupoVigente ?? null) !== null; // con grupo vigente, semestre y especialidad no cambian
@endphp

<div class="campo-grupo">
    <label class="etiqueta" for="nombre">Nombre(s) *</label>
    <input class="entrada @error('nombre') invalido @enderror" type="text" id="nombre" name="nombre" maxlength="60"
           oninput="{{ $filtroNombre }}" value="{{ $dato('nombre', $alumno->nombre ?? '') }}" placeholder="Ej. Valeria" autocomplete="off" autofocus>
</div>

<div class="fila">
    <div class="campo-grupo">
        <label class="etiqueta" for="apellido_pat">Apellido paterno *</label>
        <input class="entrada @error('apellido_pat') invalido @enderror" type="text" id="apellido_pat" name="apellido_pat" maxlength="60"
               oninput="{{ $filtroNombre }}" value="{{ $dato('apellido_pat', $alumno->apellido_pat ?? '') }}" placeholder="Ej. Torres" autocomplete="off">
    </div>
    <div class="campo-grupo">
        <label class="etiqueta" for="apellido_mat">Apellido materno</label>
        <input class="entrada @error('apellido_mat') invalido @enderror" type="text" id="apellido_mat" name="apellido_mat" maxlength="60"
               oninput="{{ $filtroNombre }}" value="{{ $dato('apellido_mat', $alumno->apellido_mat ?? '') }}" placeholder="Opcional" autocomplete="off">
    </div>
</div>

<div class="fila">
    <div class="campo-grupo">
        @if ($modo === 'crear')
            <label class="etiqueta" for="usuario">Matrícula *</label>
            {{-- Formato libre: cualquier carácter menos espacios (máximo 30) --}}
            <input class="entrada @error('usuario') invalido @enderror" type="text" id="usuario" name="usuario" maxlength="30"
                   oninput="this.value = this.value.replace(/\s/g, '')" value="{{ $dato('usuario') }}" placeholder="Ej. 237008" autocomplete="off">
        @else
            <label class="etiqueta" for="usuario">Matrícula</label>
            <input class="entrada" type="text" id="usuario" value="{{ $alumno->usuario }}" disabled>
        @endif
    </div>
    <div class="campo-grupo">
        <label class="etiqueta" for="anio_ingreso">Generación (año de ingreso) *</label>
        <input class="entrada @error('anio_ingreso') invalido @enderror" type="number" id="anio_ingreso" name="anio_ingreso"
               min="{{ \App\Models\Alumno::ANIO_INGRESO_MINIMO }}" max="{{ \App\Models\Alumno::ANIO_INGRESO_MAXIMO }}" step="1" inputmode="numeric"
               value="{{ $dato('anio_ingreso', $alumno->alumno->anio_ingreso ?? '') }}" placeholder="Ej. 2026">
    </div>
</div>

@if ($modo === 'crear')
    <div class="campo-grupo">
        <label class="etiqueta" for="contrasena">Contraseña temporal *</label>
        <div class="campo-con-boton">
            <input class="entrada @error('contrasena') invalido @enderror" type="text" id="contrasena" name="contrasena" minlength="8"
                   x-model="contrasena" placeholder="Mínimo 8 caracteres" autocomplete="off" spellcheck="false">
            <button type="button" class="btn btn-borde" @click="generarOtra()">Generar otra</button>
        </div>
        <p class="ayuda">Entrégasela directamente al alumno. Deberá cambiarla en su primer ingreso.</p>
    </div>
@endif

<div class="campo-grupo">
    <label class="etiqueta" for="semestre">Semestre *</label>
    {{-- Deshabilitado no se envía: con grupo vigente el servidor conserva el semestre guardado --}}
    <select class="entrada @error('semestre') invalido @enderror" id="semestre" name="semestre" x-model="semestre"
            @change="if (!requiereEspecialidad()) especialidad = ''" @disabled($bloqueado)>
        <option value="">Del 1 al 6</option>
        @for ($numero = \App\Models\Alumno::SEMESTRE_MINIMO; $numero <= \App\Models\Alumno::SEMESTRE_MAXIMO; $numero++)
            <option value="{{ $numero }}">{{ $numero }}</option>
        @endfor
    </select>
</div>

<div class="campo-grupo">
    <label class="etiqueta" for="id_especialidad">Área de especialidad<span x-show="requiereEspecialidad()"> *</span></label>
    <select class="entrada @error('id_especialidad') invalido @enderror" id="id_especialidad" name="id_especialidad" x-model="especialidad"
            :disabled="!requiereEspecialidad() || @js($bloqueado)">
        <option value="" x-text="requiereEspecialidad() ? 'Selecciona' : 'Disponible desde 5.º semestre'">Disponible desde 5.º semestre</option>
        @foreach ($especialidades as $especialidad)
            <option value="{{ $especialidad->id }}">{{ $especialidad->descripcion }}</option>
        @endforeach
    </select>
</div>

<div class="fila">
    <div class="campo-grupo">
        <label class="etiqueta" for="correo_electronico">Correo electrónico *</label>
        <input class="entrada @error('correo_electronico') invalido @enderror" type="email" id="correo_electronico" name="correo_electronico" maxlength="120"
               value="{{ $dato('correo_electronico', $alumno->correo_electronico ?? '') }}" placeholder="nombre@escuela.mx" autocomplete="off">
    </div>
    <div class="campo-grupo">
        <label class="etiqueta" for="nss">NSS</label>
        <input class="entrada @error('nss') invalido @enderror" type="text" id="nss" name="nss" maxlength="11" inputmode="numeric"
               oninput="this.value = this.value.replace(/\D/g, '')" value="{{ $dato('nss', $alumno->alumno->nss ?? '') }}" placeholder="Opcional, 11 dígitos" autocomplete="off">
    </div>
</div>

<div class="campo-grupo">
    <label class="etiqueta" for="curp">CURP *</label>
    <input class="entrada campo-mayusculas @error('curp') invalido @enderror" type="text" id="curp" name="curp" maxlength="18"
           value="{{ $dato('curp', $alumno->alumno->curp ?? '') }}" placeholder="Ej. TOMV050612MVZRRLA0" autocomplete="off">
</div>

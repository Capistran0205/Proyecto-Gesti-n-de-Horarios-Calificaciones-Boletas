{{-- Campos del alta y la edición de docentes. Variables: $modo ('crear' | 'editar'), $areas, $grados y, al editar, $docente
     (usuario con docente y estudios), $resumenDisponibilidad y $disponibilidadBloqueada.
     El formulario que lo incluye declara el x-data con estudiosDocente() y, en el alta, disponibilidadDocente(). --}}
@php
    $dato = fn (string $campo, $porDefecto = '') => old($campo, $porDefecto);
    $filtroNombre = "this.value = this.value.replace(/[^\\p{L}\\p{M} ]/gu, '')";
@endphp

<div class="campo-grupo">
    <label class="etiqueta" for="nombre">Nombre(s) *</label>
    <input class="entrada @error('nombre') invalido @enderror" type="text" id="nombre" name="nombre" maxlength="60"
           oninput="{{ $filtroNombre }}" value="{{ $dato('nombre', $docente->nombre ?? '') }}" placeholder="Ej. Ana María" autocomplete="off" autofocus>
</div>

<div class="fila">
    <div class="campo-grupo">
        <label class="etiqueta" for="apellido_pat">Apellido paterno *</label>
        <input class="entrada @error('apellido_pat') invalido @enderror" type="text" id="apellido_pat" name="apellido_pat" maxlength="60"
               oninput="{{ $filtroNombre }}" value="{{ $dato('apellido_pat', $docente->apellido_pat ?? '') }}" placeholder="Ej. López" autocomplete="off">
    </div>
    <div class="campo-grupo">
        <label class="etiqueta" for="apellido_mat">Apellido materno</label>
        <input class="entrada @error('apellido_mat') invalido @enderror" type="text" id="apellido_mat" name="apellido_mat" maxlength="60"
               oninput="{{ $filtroNombre }}" value="{{ $dato('apellido_mat', $docente->apellido_mat ?? '') }}" placeholder="Opcional" autocomplete="off">
    </div>
</div>

@if ($modo === 'crear')
    <div class="campo-grupo">
        <label class="etiqueta" for="curp">CURP *</label>
        <input class="entrada campo-mayusculas @error('curp') invalido @enderror" type="text" id="curp" name="curp" maxlength="18"
               value="{{ $dato('curp') }}" placeholder="Ej. LOPA900101MVZPZN05" autocomplete="off">
    </div>

    <div class="fila">
        <div class="campo-grupo">
            <label class="etiqueta" for="rfc">RFC *</label>
            <input class="entrada campo-mayusculas @error('rfc') invalido @enderror" type="text" id="rfc" name="rfc" maxlength="13"
                   value="{{ $dato('rfc') }}" placeholder="Ej. LOPA900101AB1" autocomplete="off">
        </div>
        <div class="campo-grupo">
            <label class="etiqueta" for="usuario">Número de empleado *</label>
            <input class="entrada @error('usuario') invalido @enderror" type="text" id="usuario" name="usuario" maxlength="30" inputmode="numeric"
                   oninput="this.value = this.value.replace(/\D/g, '')" value="{{ $dato('usuario') }}" placeholder="Ej. 103509" autocomplete="off">
        </div>
    </div>

    <div class="campo-grupo">
        <label class="etiqueta" for="correo_electronico">Correo electrónico *</label>
        <input class="entrada @error('correo_electronico') invalido @enderror" type="email" id="correo_electronico" name="correo_electronico" maxlength="120"
               value="{{ $dato('correo_electronico') }}" placeholder="nombre@escuela.mx" autocomplete="off">
    </div>

    <div class="campo-grupo">
        <label class="etiqueta" for="contrasena">Contraseña temporal *</label>
        <div class="campo-con-boton">
            <input class="entrada @error('contrasena') invalido @enderror" type="text" id="contrasena" name="contrasena" minlength="8"
                   x-model="contrasena" placeholder="Mínimo 8 caracteres" autocomplete="off" spellcheck="false">
            <button type="button" class="btn btn-borde" @click="generarOtra()">Generar otra</button>
        </div>
        <p class="ayuda">Entrégasela directamente al docente. Deberá cambiarla en su primer ingreso.</p>
    </div>
@else
    <div class="campo-grupo">
        <label class="etiqueta" for="usuario">Número de empleado</label>
        <input class="entrada" type="text" id="usuario" value="{{ $docente->usuario }}" disabled>
    </div>

    <div class="campo-grupo">
        <label class="etiqueta" for="correo_electronico">Correo electrónico *</label>
        <input class="entrada @error('correo_electronico') invalido @enderror" type="email" id="correo_electronico" name="correo_electronico" maxlength="120"
               value="{{ $dato('correo_electronico', $docente->correo_electronico) }}" placeholder="nombre@escuela.mx" autocomplete="off">
    </div>

    <div class="fila">
        <div class="campo-grupo">
            <label class="etiqueta" for="curp">CURP *</label>
            <input class="entrada campo-mayusculas @error('curp') invalido @enderror" type="text" id="curp" name="curp" maxlength="18"
                   value="{{ $dato('curp', $docente->docente->curp) }}" placeholder="Ej. LOPA900101MVZPZN05" autocomplete="off">
        </div>
        <div class="campo-grupo">
            <label class="etiqueta" for="rfc">RFC *</label>
            <input class="entrada campo-mayusculas @error('rfc') invalido @enderror" type="text" id="rfc" name="rfc" maxlength="13"
                   value="{{ $dato('rfc', $docente->docente->rfc) }}" placeholder="Ej. LOPA900101AB1" autocomplete="off">
        </div>
    </div>
@endif

<div class="campo-grupo">
    <label class="etiqueta" for="id_area_disciplinar">Área disciplinar *</label>
    <select class="entrada @error('id_area_disciplinar') invalido @enderror" id="id_area_disciplinar" name="id_area_disciplinar">
        <option value="">Selecciona</option>
        @foreach ($areas as $area)
            <option value="{{ $area->id }}" @selected((string) $dato('id_area_disciplinar', $docente->docente->id_area_disciplinar ?? '') === (string) $area->id)>{{ $area->descripcion }}</option>
        @endforeach
    </select>
</div>

<div class="campo-grupo">
    <label class="etiqueta" for="horas_plaza">Horas de plaza *</label>
    <input class="entrada @error('horas_plaza') invalido @enderror" type="number" id="horas_plaza" name="horas_plaza" min="2" max="40" step="1" inputmode="numeric"
           value="{{ $dato('horas_plaza', $docente->docente->horas_plaza ?? '') }}" placeholder="Ej. 20">
    <p class="ayuda">Las horas de plaza van de 2 a 40 (40 = tiempo completo).</p>
</div>

{{-- Estudios: filas que se agregan y se quitan (de 1 a 4) --}}
<div class="bloque-punteado">
    <div class="bloque-encabezado">
        <div>
            <strong>Estudios *</strong>
            <p class="ayuda">{{ $modo === 'crear' ? 'Agrega al menos un estudio.' : 'Debe quedar al menos un estudio.' }}</p>
        </div>
        <button type="button" class="btn btn-borde" @click="agregarEstudio()" :disabled="estudios.length >= maximoEstudios">+ Agregar estudio</button>
    </div>

    <template x-for="(fila, indice) in estudios" :key="fila.id">
        <div class="estudio-fila">
            <div class="campo-grupo">
                <label class="etiqueta">Grado</label>
                <select class="entrada" :name="'estudios[' + indice + '][grado_estudio]'" x-model="fila.grado">
                    <option value="">Selecciona</option>
                    @foreach ($grados as $valor => $texto)
                        <option value="{{ $valor }}">{{ $texto }}</option>
                    @endforeach
                </select>
            </div>
            <div class="campo-grupo">
                <label class="etiqueta">Descripción</label>
                <input class="entrada" type="text" maxlength="120" :name="'estudios[' + indice + '][descripcion]'"
                       x-model="fila.descripcion" placeholder="Ej. Licenciatura en Informática" autocomplete="off">
            </div>
            <button type="button" class="btn btn-mini quitar" @click="quitarEstudio(indice)" :disabled="estudios.length <= 1">Quitar</button>
        </div>
    </template>
</div>

{{-- Disponibilidad semanal --}}
@if ($modo === 'crear')
    <div class="bloque-punteado bloque-fila">
        <div>
            <strong>Disponibilidad Semanal *</strong>
            <p class="ayuda" x-text="definida ? resumenDisp() : 'Aún sin definir'">Aún sin definir</p>
        </div>
        <button type="button" class="btn btn-borde" @click="abrirDisponibilidad()">Definir disponibilidad semanal</button>
    </div>
@else
    <div class="bloque-punteado bloque-fila">
        <div>
            <strong>Disponibilidad Semanal</strong>
            <p class="ayuda">{{ $resumenDisponibilidad }}@if ($disponibilidadBloqueada) · No se puede modificar mientras tenga horario asignado @endif</p>
        </div>
        @if ($disponibilidadBloqueada)
            <span class="btn btn-gris" aria-disabled="true">Editar ocupación semanal</span>
        @else
            <a href="{{ route('admin.docentes.disponibilidad.edit', $docente) }}" class="btn btn-borde">Editar ocupación semanal</a>
        @endif
    </div>
@endif

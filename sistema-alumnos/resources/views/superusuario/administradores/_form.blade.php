{{-- Campos compartidos por el alta y la edición. Variables: $modo ('crear' | 'editar'), $administrador (solo en editar) --}}
@php
    $dato = fn (string $campo, $porDefecto = '') => old($campo, $porDefecto);
@endphp

<div class="campo-grupo">
    <label class="etiqueta" for="nombre">Nombre(s) *</label>
    <input class="entrada @error('nombre') invalido @enderror" type="text" id="nombre" name="nombre" maxlength="60"
           oninput="this.value = this.value.replace(/[^\p{L}\p{M} ]/gu, '')"
           value="{{ $dato('nombre', $administrador->nombre ?? '') }}" placeholder="Ej. Ana María" autocomplete="off" autofocus>
</div>

<div class="fila">
    <div class="campo-grupo">
        <label class="etiqueta" for="apellido_pat">Apellido paterno *</label>
        <input class="entrada @error('apellido_pat') invalido @enderror" type="text" id="apellido_pat" name="apellido_pat" maxlength="60"
               oninput="this.value = this.value.replace(/[^\p{L}\p{M} ]/gu, '')"
               value="{{ $dato('apellido_pat', $administrador->apellido_pat ?? '') }}" placeholder="Ej. López" autocomplete="off">
    </div>
    <div class="campo-grupo">
        <label class="etiqueta" for="apellido_mat">Apellido materno</label>
        <input class="entrada @error('apellido_mat') invalido @enderror" type="text" id="apellido_mat" name="apellido_mat" maxlength="60"
               oninput="this.value = this.value.replace(/[^\p{L}\p{M} ]/gu, '')"
               value="{{ $dato('apellido_mat', $administrador->apellido_mat ?? '') }}" placeholder="Opcional" autocomplete="off">
    </div>
</div>

<div class="fila">
    <div class="campo-grupo">
        <label class="etiqueta" for="curp">CURP *</label>
        <input class="entrada @error('curp') invalido @enderror" type="text" id="curp" name="curp" maxlength="18" style="text-transform: uppercase"
               value="{{ $dato('curp', $administrador->administrador->curp ?? '') }}" placeholder="Ej. LOPA900101MVZPZN05" autocomplete="off">
    </div>
    <div class="campo-grupo">
        <label class="etiqueta" for="rfc">RFC *</label>
        <input class="entrada @error('rfc') invalido @enderror" type="text" id="rfc" name="rfc" maxlength="13" style="text-transform: uppercase"
               value="{{ $dato('rfc', $administrador->administrador->rfc ?? '') }}" placeholder="Ej. LOPA900101AB1" autocomplete="off">
    </div>
</div>

<div class="campo-grupo">
    @if ($modo === 'crear')
        <label class="etiqueta" for="usuario">Número de empleado *</label>
        <input class="entrada @error('usuario') invalido @enderror" type="text" id="usuario" name="usuario" maxlength="30" inputmode="numeric"
               oninput="this.value = this.value.replace(/\D/g, '')"
               value="{{ $dato('usuario') }}" placeholder="Ej. 103509" autocomplete="off">
    @else
        {{-- El número de empleado no se modifica --}}
        <label class="etiqueta" for="usuario">Número de empleado</label>
        <input class="entrada" type="text" id="usuario" value="{{ $administrador->usuario }}" disabled>
    @endif
</div>

<div class="campo-grupo">
    <label class="etiqueta" for="correo_electronico">Correo electrónico *</label>
    <input class="entrada @error('correo_electronico') invalido @enderror" type="email" id="correo_electronico" name="correo_electronico" maxlength="120"
           value="{{ $dato('correo_electronico', $administrador->correo_electronico ?? '') }}" placeholder="nombre@escuela.mx" autocomplete="off">
    @if ($modo === 'editar')
        <p class="ayuda">El correo, la CURP y el RFC deben ser únicos, incluso frente a administradores dados de baja.</p>
    @endif
</div>

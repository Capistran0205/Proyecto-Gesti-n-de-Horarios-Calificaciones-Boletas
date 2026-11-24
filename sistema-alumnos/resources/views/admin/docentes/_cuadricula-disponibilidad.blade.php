{{-- Cuadrícula Día · Desde · Hasta. Requiere un x-data con disponibilidadDocente() y un método habilitado() --}}
@php
    $bloquesHorario = config('horario.bloques');
    $inicioEscolar = $bloquesHorario[array_key_first($bloquesHorario)]['inicio'];
    $finEscolar = $bloquesHorario[array_key_last($bloquesHorario)]['fin'];
    $formato12 = fn (string $hora) => \Carbon\Carbon::createFromFormat('H:i', $hora)->format('g:i') . ' ' . ((int) substr($hora, 0, 2) >= 12 ? 'p. m.' : 'a. m.');
@endphp

<div class="botones-fila">
    <button type="button" class="btn btn-borde" @click="todosLosDias()">Lunes a viernes</button>
    <button type="button" class="btn btn-borde" @click="limpiar()">Limpiar</button>
</div>

<div class="cuadricula-disp">
    <div class="disp-fila disp-encabezado"><span>Día</span><span>Desde</span><span>Hasta</span></div>

    @foreach (config('horario.dias') as $dia => $nombre)
        <div class="disp-fila">
            <label class="dia-check">
                <input type="checkbox" name="disponibilidad[{{ $dia }}][activo]" value="1"
                       x-model="disp[{{ $dia }}].activo" :disabled="!habilitado()">
                <span>{{ $nombre }}</span>
            </label>
            <input class="entrada" type="time" name="disponibilidad[{{ $dia }}][desde]" min="{{ $inicioEscolar }}" max="{{ $finEscolar }}"
                   aria-label="Desde, {{ $nombre }}" x-model="disp[{{ $dia }}].desde" :disabled="!habilitado() || !disp[{{ $dia }}].activo">
            <input class="entrada" type="time" name="disponibilidad[{{ $dia }}][hasta]" min="{{ $inicioEscolar }}" max="{{ $finEscolar }}"
                   aria-label="Hasta, {{ $nombre }}" x-model="disp[{{ $dia }}].hasta" :disabled="!habilitado() || !disp[{{ $dia }}].activo">
        </div>
    @endforeach
</div>

<p class="texto-error" x-show="errorDisp" x-text="errorDisp" x-cloak></p>
<p class="ayuda">Selecciona al menos un día. El horario escolar es de {{ $formato12($inicioEscolar) }} a {{ $formato12($finEscolar) }} y la hora “Hasta” debe ser mayor que “Desde”.</p>

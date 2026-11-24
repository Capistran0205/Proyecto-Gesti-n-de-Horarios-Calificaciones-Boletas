{{-- Lógica Alpine compartida por el formulario de docentes y la pantalla de disponibilidad.
     Se declara como funciones globales para poder combinarlas en x-data sin recompilar los assets. --}}
@php
    $bloquesHorario = config('horario.bloques');
    $configDisponibilidad = [
        'dias' => config('horario.dias'),
        'cortos' => config('horario.dias_cortos'),
        'bloques' => array_values($bloquesHorario),
        'inicio' => $bloquesHorario[array_key_first($bloquesHorario)]['inicio'],
        'fin' => $bloquesHorario[array_key_last($bloquesHorario)]['fin'],
    ];
@endphp
<script>
    window.configDisponibilidad = @js($configDisponibilidad);

    // Estudios: filas dinámicas (mínimo 1, máximo $maximo)
    window.estudiosDocente = function (filas, maximo) {
        let contador = 0;
        const conId = function (fila) {
            contador++;
            return { id: contador, grado: fila.grado || '', descripcion: fila.descripcion || '' };
        };

        return {
            estudios: (filas.length ? filas : [{}]).map(conId),
            maximoEstudios: maximo,
            agregarEstudio() { if (this.estudios.length < this.maximoEstudios) this.estudios.push(conId({})); },
            quitarEstudio(indice) { if (this.estudios.length > 1) this.estudios.splice(indice, 1); },
        };
    };

    // Disponibilidad semanal: por día, casilla + rango "Desde-Hasta". El servidor la convierte a bloques de clase.
    window.disponibilidadDocente = function (rangos) {
        const config = window.configDisponibilidad;

        const textoDeDias = function (dias) {
            const primero = config.cortos[dias[0]];
            const ultimo = config.cortos[dias[dias.length - 1]];
            if (dias.length >= 3 && dias[dias.length - 1] - dias[0] + 1 === dias.length) return primero + ' a ' + ultimo;
            if (dias.length === 2) return primero + ' y ' + ultimo;
            return dias.map(function (dia) { return config.cortos[dia]; }).join(', ');
        };

        return {
            disp: rangos,
            errorDisp: '',
            copiaDisp: null,
            todosLosDias() { Object.values(this.disp).forEach(function (d) { d.activo = true; }); },
            limpiar() { Object.values(this.disp).forEach(function (d) { d.activo = false; }); },
            copiar() { this.copiaDisp = JSON.parse(JSON.stringify(this.disp)); },
            restaurar() { if (this.copiaDisp) this.disp = this.copiaDisp; this.errorDisp = ''; },

            // Mismas reglas que el servidor, para avisar antes de enviar
            validarDisp() {
                this.errorDisp = '';
                const activos = Object.entries(this.disp).filter(function (par) { return par[1].activo; });

                if (activos.length === 0) {
                    this.errorDisp = 'Selecciona al menos un día de disponibilidad.';
                    return false;
                }

                for (const [dia, d] of activos) {
                    const nombre = config.dias[dia];
                    if (!d.desde || !d.hasta) {
                        this.errorDisp = nombre + ': indica la hora «Desde» y la hora «Hasta».';
                    } else if (d.desde < config.inicio || d.hasta > config.fin) {
                        this.errorDisp = nombre + ': el horario debe estar entre ' + config.inicio + ' y ' + config.fin + '.';
                    } else if (d.hasta <= d.desde) {
                        this.errorDisp = nombre + ': la hora «Hasta» debe ser mayor que «Desde».';
                    } else if (!config.bloques.some(function (b) { return b.inicio >= d.desde && b.fin <= d.hasta; })) {
                        this.errorDisp = nombre + ': el rango debe incluir al menos un bloque de clase completo (50 minutos).';
                    }
                    if (this.errorDisp) return false;
                }

                return true;
            },

            // "Lun a Vie · 15:00 a 21:20"
            resumenDisp() {
                const grupos = {};
                Object.entries(this.disp).forEach(function (par) {
                    if (!par[1].activo) return;
                    const horario = par[1].desde + ' a ' + par[1].hasta;
                    (grupos[horario] = grupos[horario] || []).push(Number(par[0]));
                });

                const horarios = Object.keys(grupos);
                if (horarios.length === 0) return 'Aún sin definir';

                return horarios.map(function (horario) { return textoDeDias(grupos[horario]) + ' · ' + horario; }).join(' | ');
            },
        };
    };
</script>

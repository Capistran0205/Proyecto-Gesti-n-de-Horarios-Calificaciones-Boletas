<?php

// Turno vespertino único (15:00 a 21:20). Esta tabla se define una sola vez y la usan
// todas las validaciones y vistas de horario (disponibilidad del docente, horarios de grupo, etc.).
return [

    // dia_semana: 1 = lunes ... 5 = viernes
    'dias' => [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
    ],

    'dias_cortos' => [
        1 => 'Lun',
        2 => 'Mar',
        3 => 'Mié',
        4 => 'Jue',
        5 => 'Vie',
    ],

    // Bloques de clase de 50 minutos (no son horas del reloj)
    'bloques' => [
        1 => ['inicio' => '15:00', 'fin' => '15:50'],
        2 => ['inicio' => '15:50', 'fin' => '16:40'],
        3 => ['inicio' => '16:40', 'fin' => '17:30'],
        4 => ['inicio' => '17:50', 'fin' => '18:40'],
        5 => ['inicio' => '18:40', 'fin' => '19:30'],
        6 => ['inicio' => '19:40', 'fin' => '20:30'],
        7 => ['inicio' => '20:30', 'fin' => '21:20'],
    ],

    // Descansos entre bloques (no se asignan clases)
    'descansos' => [
        ['despues_del_bloque' => 3, 'inicio' => '17:30', 'fin' => '17:50'],
        ['despues_del_bloque' => 5, 'inicio' => '19:30', 'fin' => '19:40'],
    ],

];

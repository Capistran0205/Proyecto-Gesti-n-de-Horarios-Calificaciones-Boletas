# Plan 07 — Armado de horarios (HU-07 y HU-08)

**Historias:**
- HU-07: Como administrador, quiero crear el horario de un docente con sus materias y aulas, considerando su disponibilidad, para organizar sus clases.
- HU-08: Como administrador, quiero asignar el horario a los grupos conformados por alumnos.

**Rol que opera:** Administrador (rol 1). **Depende de:** Planes 02, 03, 04 y 06.

> Es el módulo más complejo. Conviene integrarlo con la capa Modelo y Controlador revisadas con calma antes de las vistas.

## Tablas
`horario_grupo` (1:1 con `grupo`) y `detalle_horario_grupo` (cada clase: docente + materia + salón + día + bloque).

## Modelo de trabajo
Hay **un solo horario**: el del grupo. Cada celda (día × bloque) del grupo se llena con materia + docente + aula. El **horario del docente** (HU-07) es la vista de todas sus celdas en los grupos del periodo; no se guarda aparte. Por eso HU-07 y HU-08 se integran juntas.

## Reglas de validación de una clase (celda)
Al guardar una celda se valida, en este orden y con mensaje claro para cada caso:
1. **Grupo:** la celda (día, bloque) del grupo está libre (`UNIQUE` en la BD).
2. **Materia:** activa y válida para el grupo: tronco común del mismo semestre, o de especialidad con la misma especialidad del grupo (`Materia::paraGrupo`).
3. **Docente:** activo y con la materia en `docente_materia` (también lo exige la FK compuesta).
4. **Disponibilidad:** el docente está disponible en ese día y bloque (`Docente::estaDisponible`).
5. **Choque de docente:** no tiene otra clase en el mismo día y bloque en **otro grupo del mismo periodo**.
6. **Choque de aula:** el aula no está ocupada en el mismo día y bloque en el mismo periodo.
7. **Tiempo completo (RN-HOR-01):** si `horas_plaza = 40`, máximo **3 materias distintas** en el periodo; la misma materia en varios grupos cuenta como una.

Los choques 5 y 6 cruzan `detalle_horario_grupo → horario_grupo → grupo.id_periodo`.

## Avisos de horas (no bloquean el guardado)
Las clases deben coincidir con las horas de la materia y con la plaza del docente. Mientras el horario se arma, los totales van incompletos; por eso estas reglas **no impiden guardar la celda**: se muestra el aviso y la celda se guarda.

8. **Horas de la materia en el grupo:** el número de clases de esa materia en el grupo debe ser igual a `materia.horas_semana`.
   - Al guardar: si la celda hace que se **rebasen**, aviso "La materia X ya tiene N de M horas en este grupo".
   - En la cuadrícula: resumen por materia con horas asignadas / requeridas (completa, faltan N, sobran N).
9. **Horas de plaza del docente:** el total de clases del docente en el periodo (todos sus grupos) debe ser igual a `docente.horas_plaza`.
   - Al guardar: si la celda hace que se **rebasen**, aviso "El docente X tendría N horas y su plaza es de M".
   - En el horario del docente y en el listado: horas asignadas / horas de plaza.
10. **Horario completo:** un grupo se marca como "completo" en el listado solo cuando todas sus materias tienen exactamente sus `horas_semana`; si no, "incompleto" con el detalle de faltantes o sobrantes.

> Cada clase vale 1 hora (bloque de 50 min), igual que en la escuela.

## Capa Modelo
- `HorarioGrupo`: `$table = 'horario_grupo'`, fechas mapeadas, `grupo()`, `clases()` (`hasMany` DetalleHorarioGrupo).
- `DetalleHorarioGrupo`: `$table = 'detalle_horario_grupo'`, `$timestamps = false`, relaciones `horario()`, `docente()`, `materia()`, `salon()`.
  - Scope `delPeriodo($idPeriodo)` (join con `horario_grupo` y `grupo`).
  - Scope `enDiaBloque($dia, $bloque)`.
- `config/horario.php` con la tabla de bloques (si no existe ya).
- **`app/Services/ValidadorHorario.php`** (se justifica porque lo usan la captura y la edición de celdas): un método público `validarClase(array $datos, ?int $idDetalleIgnorar = null): array` que devuelve `['errores' => [...], 'avisos' => [...]]` (reglas 1–7 son errores; 8 y 9 son avisos) y un método privado por regla. Más dos métodos de resumen que usa la vista: `resumenHorasGrupo(Grupo $grupo): array` y `horasAsignadasDocente(int $idDocente, int $idPeriodo): int`.

## Capa Controlador
- `Admin\HorarioGrupoController`:
  - `index`: grupos del periodo vigente con indicador de horario completo/incompleto.
  - `edit(Grupo $grupo)`: crea el `horario_grupo` si no existe (`firstOrCreate`) y entrega la cuadrícula 5 × 7 con las clases actuales, materias válidas, docentes que pueden darlas y aulas.
- `Admin\ClaseHorarioController`:
  - `store`: agrega una clase a una celda (usa `ValidadorHorario`; si hay **errores** no guarda y los regresa; si solo hay **avisos**, guarda y los regresa junto con el resumen de horas actualizado).
  - `update`: cambia docente, materia o aula de una celda.
  - `destroy`: vacía una celda.
  - Respuestas **JSON** cuando la petición es AJAX (la cuadrícula usa Alpine con `fetch`) y redirección con mensajes cuando no.
- `Admin\HorarioDocenteController@show(Docente $docente)`: cuadrícula del docente en el periodo vigente (HU-07, vista de consulta para el administrador).
- Endpoint de apoyo `GET admin/horarios/docentes-disponibles?materia=&dia=&bloque=&periodo=`: docentes que pueden dar la materia, están disponibles y no tienen choque (facilita la captura).
- `StoreClaseRequest`: `id_materia`, `id_docente`, `id_salon` con `exists`; `dia_semana` 1–5; `bloque` 1–7.

## Capa Vista — solicitar interfaces
Pide mockups de: listado de grupos con estado del horario, cuadrícula de captura del grupo (cómo se elige materia/docente/aula en cada celda: modal, panel lateral o selects en línea), y horario del docente visto por el administrador. Las cuadrículas usan la tabla de `config/horario.php` e incluyen los dos descansos como filas fijas.

## Criterios de aceptación
- El administrador arma el horario de un grupo celda por celda.
- El sistema impide: materia que no corresponde al grupo, docente sin esa materia, docente no disponible, choque de docente, choque de aula y más de 3 materias distintas para tiempo completo.
- El horario del docente se refleja automáticamente al llenar los grupos.
- Se muestra aviso (sin bloquear) cuando una materia rebasa sus horas semanales en el grupo o el docente rebasa sus horas de plaza; la cuadrícula y el horario del docente muestran horas asignadas contra requeridas.

## Decisiones tomadas
- Las clases de una materia en el grupo deben coincidir con `horas_semana`, y el total del docente con `horas_plaza`; si no coinciden, se muestra el aviso correspondiente.

## Preguntas abiertas (resolver en Fase 0)
1. ¿Las materias de aula especial deben ir solo en aulas tipo 2? (El esquema no relaciona materia con tipo de aula.)
2. ¿Se requiere copiar el horario de un grupo a otro del mismo semestre?

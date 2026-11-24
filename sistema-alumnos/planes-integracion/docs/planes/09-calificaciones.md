# Plan 09 — Carga y edición de calificaciones (HU-11)

**Historia:** Como docente, quiero cargar y editar las calificaciones de mis alumnos por bloque.
**Rol que opera:** Docente (rol 2). **Depende de:** Plan 07 (y reutiliza consultas del Plan 08).

## Tablas
`calificacion_alumno` (+ lectura de horario y grupos).

## Reglas de negocio
- Tres bloques (parciales) por semestre: `bloque` 1, 2 o 3 (no confundir con el bloque horario 1–7).
- El docente calcula la calificación fuera del sistema (evidencias 70 % + examen 30 %) y **captura solo el resultado** por bloque.
- Escala 5–10, admite decimales (`NUMERIC(4,2)`).
- El docente solo captura en grupo + materia que **imparte en el periodo vigente**, y solo para alumnos inscritos en ese grupo.
- Única por `(id_alumno, id_materia, id_periodo, bloque)`: capturar de nuevo **actualiza** (upsert).
- Puede editar en cualquier momento: apertura y cierre de captura quedan fuera de alcance.
- La calificación final (promedio de los tres bloques con redondeo .5 hacia arriba) **no se guarda**: se calcula al mostrarla (Planes 10 y 11).

## Capa Modelo
- `CalificacionAlumno`: `$table = 'calificacion_alumno'`, fechas mapeadas, `$fillable`, cast `calificacion => 'decimal:2'`. Relaciones: `docente()`, `alumno()`, `grupo()`, `materia()`, `periodo()`.
- Clase de apoyo **`app/Support/Calificacion.php`** (estática, sin estado) porque la usan los Planes 09, 10 y 11:
  - `redondear(float $valor): int` → `(int) floor($valor + 0.5)`.
  - `esAprobatoria(int $valor): bool` → `>= 6`.
  - `paraMostrar(?float $valor): ?int` → redondeada; si no es aprobatoria, `5`.
  - `final(array $bloques): ?float` → promedio solo si están los tres bloques; si no, `null`.
- En `Docente`: `imparte(int $idGrupo, int $idMateria, int $idPeriodo): bool`.

## Capa Controlador
- `Docente\CalificacionController`:
  - `index`: combinaciones grupo + materia que imparte en el periodo vigente, con avance de captura por bloque.
  - `edit(Grupo $grupo, Materia $materia, int $bloque)`: lista de alumnos del grupo con su calificación actual de ese bloque. `abort(403)` si no la imparte.
  - `update(...)`: recibe `calificaciones[id_alumno] => valor`; guarda con `upsert()` en una transacción; ignora filas vacías (alumno sin capturar todavía).
- `GuardarCalificacionesRequest`: `bloque` in:1,2,3; `calificaciones.*` `nullable|numeric|between:5,10`; las claves deben ser alumnos del grupo (validar en `after()`).

## Capa Vista — solicitar interfaces
Pide mockups de: selección de grupo/materia/bloque y tabla de captura (alumno + campo numérico). En la captura, Alpine marca en rojo al instante el valor que, redondeado, no sea aprobatorio, y avisa si se sale de la página con cambios sin guardar.

## Criterios de aceptación
- El docente captura y edita calificaciones por bloque solo de sus grupos y materias.
- No se aceptan valores fuera de 5–10.
- Volver a guardar actualiza sin duplicar.

## Preguntas abiertas (resolver en Fase 0)
1. ¿Se captura con decimales (p. ej. 7.45) o ya redondeado? (El esquema permite decimales.)
2. ¿Se requiere importar las calificaciones desde el Excel del docente? (Propuesta: fuera de esta versión.)

# Plan 06 — Gestión de grupos y asignación de alumnos (HU-06)

**Historia:** Como administrador, quiero gestionar grupos y asignarles alumnos para organizarlos.
**Incluye:** el SuperUsuario puede incrementar la capacidad de un grupo.
**Rol que opera:** Administrador (rol 1) y SuperUsuario (rol 0, solo capacidad). **Depende de:** Plan 02 (periodo, especialidad), Plan 05 (alumnos).

## Tablas
`grupo`, `detalle_grupo` (+ lectura de `alumno`, `periodo_escolar`, `especialidad`).

## Reglas de negocio
- Un grupo = periodo + semestre + letra (A–Z), único por `(id_periodo, semestre, letra)`.
- Grupos de 5.º y 6.º son de especialidad: `id_especialidad` obligatoria; en 1.º–4.º, vacía (`CHECK`).
- **Capacidad:** el administrador crea grupos con tope de 40 (no puede subirlo). Solo el SuperUsuario modifica `capacidad_max`, sin límite superior, y nunca por debajo de los alumnos ya inscritos.
- **Asignar alumnos:** solo alumnos activos, del **mismo semestre** que el grupo y, en grupos de especialidad, de la **misma especialidad**.
- Un alumno solo puede estar en **un grupo por periodo**.
- No se puede rebasar `capacidad_max`.
- Quitar a un alumno de un grupo borra su fila de `detalle_grupo` (es una asignación, no un usuario).
- Nomenclatura propuesta por el equipo (10n a 60n por semestre, generación, siglas de especialidad): **solo de presentación**; en la BD se guardan semestre + letra.

## Capa Modelo
- `Grupo`: `$table = 'grupo'`, fechas mapeadas.
  - Relaciones: `periodo()`, `especialidad()`, `alumnos()` (`belongsToMany` Alumno vía `detalle_grupo`, claves `id_grupo`/`id_alumno`), `horario()` (`hasOne` HorarioGrupo, Plan 07).
  - Métodos/accessors: `nombre_grupo` (p. ej. `"5A"` + siglas si aplica), `lugaresDisponibles()`, `estaLleno()`.
  - Scope `delPeriodo($idPeriodo)`.
- `DetalleGrupo` solo si hace falta; con `belongsToMany` normalmente basta.

## Capa Controlador
- `Admin\GrupoController`: `index` (filtro por periodo y semestre), `create`, `store`, `show` (con alumnos inscritos), `edit`, `update`. En `store` la capacidad siempre es 40.
- `Admin\GrupoAlumnoController`:
  - `create`: lista de alumnos elegibles (activos, mismo semestre, misma especialidad, sin grupo en ese periodo).
  - `store`: recibe un array de `id_alumno`; valida elegibilidad y capacidad **dentro de una transacción** con `lockForUpdate()` sobre el grupo para evitar rebasar el cupo con dos capturas simultáneas.
  - `destroy`: quita a un alumno del grupo.
- `SuperUsuario\CapacidadGrupoController` (`edit`, `update`): `capacidad_max` entero ≥ alumnos inscritos.
- Form Requests: `StoreGrupoRequest` (unique compuesto con `Rule::unique('grupo')->where(...)`), `AsignarAlumnosRequest`, `UpdateCapacidadRequest`.

## Capa Vista — solicitar interfaces
Pide mockups de: listado de grupos, alta/edición, detalle del grupo con sus alumnos, asignación de alumnos (lista con casillas y contador de lugares) y la pantalla del SuperUsuario para cambiar la capacidad.

## Criterios de aceptación
- El administrador crea grupos por periodo; no se repite semestre + letra en el mismo periodo.
- Solo se asignan alumnos elegibles y nunca se rebasa la capacidad.
- El SuperUsuario sube la capacidad de un grupo; el administrador no puede.

## Preguntas abiertas (resolver en Fase 0)
1. Siglas de cada especialidad y cantidad de grupos por semestre (pendiente con el PO).
2. ¿Cómo se muestra el nombre del grupo en pantalla? (¿`5A-INF`, `51`, `5A`?)
3. ¿Al cambiar de periodo se necesita "promover" alumnos al siguiente semestre en bloque, o se hace a mano en el Plan 05?

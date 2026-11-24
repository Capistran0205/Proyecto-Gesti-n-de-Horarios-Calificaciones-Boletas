# Plan 05 — Gestión de alumnos (HU-04)

**Historia:** Como administrador, quiero gestionar a los alumnos para posteriormente inscribirlos en grupos.
**Rol que opera:** Administrador (rol 1). **Depende de:** Plan 02 (especialidad).

## Tablas
`usuario` (rol = 3) + `alumno`.

## Reglas de negocio
- Datos: matrícula (formato libre de la escuela; va en `usuario.usuario`, no se genera), nombre, apellidos, correo (obligatorio), CURP, NSS (opcional, 11 dígitos), año de ingreso (generación), semestre (1–6), especialidad.
- **Especialidad:** obligatoria si el semestre es 5 o 6, vacía si es 1–4 (`CHECK` en la BD). La asigna el administrador al registrar o modificar.
- Contraseña temporal **generada por el sistema** en el campo del formulario (regla en `CLAUDE.md` § 4; reutiliza `Usuario::generarContrasenaTemporal()` del Plan 01, con su ruta JSON `admin/alumnos/contrasena-temporal` para "Generar otra").
- Baja lógica conservando histórico.
- **Reactivación:** el administrador actualiza el semestre al reactivar (no es automático).
- El alumno entra con su matrícula o su correo personal (ya cubierto por HU-01).
- La auditoría de quién hizo los cambios queda fuera de alcance.

## Capa Modelo
- `Alumno`: `$table = 'alumno'`, `$primaryKey = 'id_alumno'`, `$incrementing = false`, `$timestamps = false`.
  - Relaciones: `usuario()`, `especialidad()`, `grupos()` (`belongsToMany` Grupo vía `detalle_grupo`, se usa en el Plan 06), `calificaciones()` (`hasMany`, Plan 09).
  - Método `grupoVigente()`: grupo del alumno en el periodo vigente (lo usan los Planes 10 y 11).
- En el modelo de usuario: `alumno()` (`hasOne`), scope `alumnos()`.

## Capa Controlador
- `Admin\AlumnoController`: `index` (filtros por semestre, especialidad, estado; búsqueda por nombre o matrícula), `create`, `store`, `show`, `edit`, `update`, `baja`, `reactivar`.
- `store` / `update` en transacción (usuario + alumno).
- `StoreAlumnoRequest` / `UpdateAlumnoRequest`: CURP con la regex del esquema, `nss` `nullable|digits:11`, `anio_ingreso` 2000–2100, `semestre` 1–6, `id_especialidad` `required_if` semestre 5 o 6 y `prohibited` en 1–4 (en `prepareForValidation`, forzar `null` si semestre < 5).
- `ReactivarAlumnoRequest`: solo `semestre` (y `id_especialidad` si queda en 5.º o 6.º).

## Capa Vista — solicitar interfaces
Pide mockups de: listado, registro, consulta, modificación, baja y reactivación. Con Alpine, el selector de especialidad aparece y se vuelve obligatorio cuando el semestre es 5 o 6.

## Criterios de aceptación
- Alta, consulta, edición, baja y reactivación de alumnos.
- No se puede guardar un alumno de 5.º o 6.º sin especialidad ni uno de 1.º a 4.º con especialidad.
- Al reactivar se pide el semestre.

## Decisiones tomadas
- Contraseña temporal: la genera el sistema en el campo de texto del formulario.

## Preguntas abiertas (resolver en Fase 0)
1. Lista final de datos del alumno (pendiente con el PO): ¿hace falta algo más que lo del esquema (teléfono, tutor…)? Si sí, requiere cambio de esquema aprobado.
2. ¿Se necesita carga masiva de alumnos (CSV/Excel) o solo captura individual?

# Planes de integración por módulo — Índice

Base: Product Backlog Final v2 (HU-01 a HU-14) y `esquema_preparatoria.sql`.
HU-01 (login, recuperación de contraseña y flujo por rol) **ya está iniciada**; los módulos parten de ella.

## Orden de integración

El orden sigue las dependencias de la base de datos: no se puede armar un horario sin materias, docentes, grupos y aulas.

| # | Plan | Historias | Rol | Depende de |
|---|---|---|---|---|
| 0 | [Reconocimiento inicial](#paso-0--reconocimiento-inicial-una-sola-vez) | HU-01 (existente) | — | — |
| 1 | [01-administradores.md](01-administradores.md) | HU-02 | SuperUsuario | 0 |
| 2 | [02-catalogos-aulas.md](02-catalogos-aulas.md) | Apoyo + historia propuesta de aulas | Administrador | 0 |
| 3 | [03-materias.md](03-materias.md) | HU-05 | Administrador | 2 |
| 4 | [04-docentes.md](04-docentes.md) | HU-03 | Administrador | 2, 3 |
| 5 | [05-alumnos.md](05-alumnos.md) | HU-04 | Administrador | 2 |
| 6 | [06-grupos.md](06-grupos.md) | HU-06 (+ capacidad por SuperUsuario) | Administrador / SuperUsuario | 2, 5 |
| 7 | [07-horarios.md](07-horarios.md) | HU-07, HU-08 | Administrador | 2, 3, 4, 6 |
| 8 | [08-consultas-docente.md](08-consultas-docente.md) | HU-09, HU-10 | Docente | 7 |
| 9 | [09-calificaciones.md](09-calificaciones.md) | HU-11 | Docente | 7 |
| 10 | [10-consultas-alumno.md](10-consultas-alumno.md) | HU-12, HU-13 | Alumno | 7, 9 |
| 11 | [11-boleta-pdf.md](11-boleta-pdf.md) | HU-14 | Alumno | 9, 10 |

Los módulos 3, 4 y 5 pueden repartirse entre desarrolladores en paralelo una vez cerrado el 2.

## Paso 0 — Reconocimiento inicial (una sola vez)

Antes del primer módulo, el agente:

1. Revisa `composer.json` (versión de Laravel y paquetes), `docker-compose.yml`, `.env.example`, `routes/`, `app/Models`, `app/Http/Middleware` y `resources/views`.
2. Identifica cómo está resuelto el login: modelo de usuario, columna de contraseña (`contrasena`), guard, middleware de rol, redirección por rol, layout base y componente de modal.
3. Compara los modelos existentes con `esquema_preparatoria.sql` y reporta diferencias (columnas faltantes, nombres distintos).
4. Escribe un resumen corto en `docs/planes/ESTADO_PROYECTO.md` (stack, versión, convenciones detectadas, diferencias con el esquema) y **espera aprobación**.

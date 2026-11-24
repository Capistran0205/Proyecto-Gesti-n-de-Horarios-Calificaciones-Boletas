# Plan 02 — Catálogos de apoyo y gestión de aulas

**Historias:** apoyo a HU-03 a HU-08 + historia propuesta "Como administrador, quiero gestionar aulas (incluidas las especiales) para asignarlas posteriormente a los horarios".
**Rol que opera:** Administrador (rol 1). **Depende de:** Paso 0.

> La gestión de aulas es una **historia propuesta** que el PO espera en la entrega. Confirma en la Fase 0 que el equipo ya la agregó al backlog.

## Tablas
`especialidad`, `area_disciplinar`, `periodo_escolar`, `salon`.

## Reglas de negocio
- **Especialidades** actuales: Laboratorista clínico, Informática/Tecnologías de la información y Dibujo. Se cargan con seeder (sin pantalla de gestión).
- **Áreas disciplinares** (área de las materias y del docente; distinta de la especialidad del alumno): se cargan con seeder a partir del mapa curricular.
- **Periodos escolares:** semestrales, `fecha_fin > fecha_inicio`, nombre único (p. ej. `2026-2027 A`). El **periodo vigente** es el que contiene la fecha actual; si no hay, el más reciente.
- **Aulas (`salon`):** descripción + edificio (atributo, no entidad), único por `(edificio, descripcion)`; tipo 1 = aula normal, 2 = aula especial (laboratorio, taller, cómputo).

## Capa Modelo
- Modelos `Especialidad`, `AreaDisciplinar`, `PeriodoEscolar`, `Salon` con `$table` explícita.
  - `Especialidad`, `AreaDisciplinar`, `Salon`: `const UPDATED_AT = null;`.
  - `PeriodoEscolar`: `$timestamps = false`, casts `date` en las fechas, método estático `vigente()`.
  - `Salon`: constantes `TIPO_NORMAL = 1`, `TIPO_ESPECIAL = 2`, accessor `tipo_texto`.
- Seeders: `EspecialidadSeeder`, `AreaDisciplinarSeeder` (con `firstOrCreate` para poder ejecutarlos más de una vez) y registro en `DatabaseSeeder`.
- **Verificación:** `php artisan db:seed --class=EspecialidadSeeder` y conteo en tinker.

## Capa Controlador
| Recurso | Rutas | Notas |
|---|---|---|
| Aulas | `admin/aulas` → `index, create, store, edit, update` | `Admin\SalonController`; sin baja física. |
| Periodos | `admin/periodos` → `index, create, store, edit, update` | `Admin\PeriodoEscolarController`; validar que no se traslapen fechas con otro periodo. |

- Form Requests: `StoreSalonRequest`/`UpdateSalonRequest` (unique compuesto edificio + descripción con `Rule::unique()->where()`), `StorePeriodoRequest`/`UpdatePeriodoRequest` (`after:fecha_inicio`).

## Capa Vista — solicitar interfaces
Pide mockups de: listado y formulario de aulas, listado y formulario de periodos. Si el equipo no tiene mockup de periodos, propón un formulario simple de tres campos y pide aprobación antes de generarlo.

## Criterios de aceptación
- Las especialidades y áreas disciplinares existen tras correr los seeders.
- El administrador registra y edita aulas normales y especiales; no se repite un aula en el mismo edificio.
- El administrador registra periodos sin traslape y el sistema identifica el vigente.

## Preguntas abiertas (resolver en Fase 0)
1. Lista definitiva de **áreas disciplinares** (¿se toman del mapa curricular?).
2. ¿Los periodos los gestiona el administrador en pantalla o basta con un seeder?
3. ¿Un aula puede darse de baja? El esquema no tiene `estado` en `salon`; agregarlo requiere un cambio de esquema aprobado.

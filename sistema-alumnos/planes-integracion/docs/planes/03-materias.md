# Plan 03 — Gestión de materias (HU-05)

**Historia:** Como administrador, quiero gestionar materias para posteriormente asignarlas a horarios.
**Rol que opera:** Administrador (rol 1). **Depende de:** Plan 02 (especialidad, área disciplinar).

## Tablas
`materia` (+ lectura de `especialidad` y `area_disciplinar`).

## Reglas de negocio
- Campos: nombre, clave (única, no es PK), horas semanales (un solo campo, 1–10), tipo, semestre (1–6), área disciplinar (obligatoria), especialidad.
- Tipo: **1 = "Tronco común"**, **2 = "Especialidad"**.
  - Tipo 2 ⇒ especialidad obligatoria y semestre 5 o 6.
  - Tipo 1 ⇒ especialidad vacía.
  (Son `CHECK` en la BD: valida lo mismo en el Form Request para mostrar un mensaje claro.)
- Se puede modificar nombre, clave, semestre y tipo (y los demás campos del formulario).
- Baja lógica (`estado = 0`): la materia deja de poder asignarse a docentes y horarios; las asignaciones existentes **no cambian**.
- No se muestra aviso de "ya está en un horario".

## Capa Modelo
- `app/Models/Materia.php`: `$table = 'materia'`, `CREATED_AT`/`UPDATED_AT` mapeados, `$fillable`, constantes `TIPO_TRONCO_COMUN`, `TIPO_ESPECIALIDAD`, `ESTADO_ACTIVA`, `ESTADO_BAJA`.
- Relaciones: `especialidad()`, `areaDisciplinar()` (`belongsTo`); `docentes()` (`belongsToMany` vía `docente_materia`, se usará en el Plan 04).
- Scopes: `activas()`, `delSemestre($semestre)`, `paraGrupo($semestre, $idEspecialidad)` (tronco común del semestre + especialidad del grupo; se usa en el Plan 07).
- Accessor `tipo_texto`.

## Capa Controlador
- `Admin\MateriaController`: `index` (filtros por semestre, tipo, estado y búsqueda por nombre/clave), `create`, `store`, `show`, `edit`, `update`, `baja` (PATCH).
- `StoreMateriaRequest` / `UpdateMateriaRequest`:
  - `clave` única (`ignore` en update), `horas_semana` entero 1–10, `semestre` 1–6, `tipo` in:1,2.
  - `id_especialidad`: `required_if:tipo,2`, `prohibited_if:tipo,1`, `exists:especialidad,id`.
  - Si `tipo = 2` y `semestre < 5` → error "Las materias de especialidad solo existen en 5.º y 6.º semestre".
  - En `prepareForValidation`: si tipo = 1, forzar `id_especialidad = null`.

## Capa Vista — solicitar interfaces
Pide mockups de: listado con filtros, registro, consulta, modificación y confirmación de baja. Con Alpine, el selector de especialidad se muestra y se vuelve obligatorio solo cuando el tipo es "Especialidad".

## Criterios de aceptación
- Alta, consulta, edición y baja de materias.
- No se puede guardar una materia de especialidad sin especialidad o en semestres 1–4.
- Una materia dada de baja no aparece en los selectores de docentes ni de horarios.

## Preguntas abiertas (resolver en Fase 0)
1. ¿Se cargan las materias del mapa curricular con un seeder o el administrador las captura una por una?
2. ¿Se permite reactivar una materia dada de baja?

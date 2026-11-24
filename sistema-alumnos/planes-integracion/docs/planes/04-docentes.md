# Plan 04 — Gestión de docentes (HU-03)

**Historia:** Como administrador, quiero gestionar a los docentes para tenerlos disponibles para posteriormente armar los horarios.
**Rol que opera:** Administrador (rol 1). **Depende de:** Plan 02 (área disciplinar), Plan 03 (materias).

## Tablas
`usuario` (rol = 2) + `docente` + `docente_estudio` (1:N) + `docente_materia` (N:M con `materia`).

## Reglas de negocio
- Datos: número de empleado (lo asigna la escuela; va en `usuario.usuario`), nombre, apellidos, correo (obligatorio), CURP, RFC, área disciplinar (obligatoria), horas de plaza (2–40; 40 = tiempo completo), estudios (uno o varios: descripción + grado 1–5), materias que puede impartir y disponibilidad.
- **Disponibilidad:** la captura el administrador con base en el formato externo, dentro del turno 15:00–21:20. Se guarda en `docente.disponibilidad` (JSONB). **Formato aprobado:**
  ```json
  { "1": [1,2,3,4], "2": [1,2,3,4,5,6,7], "3": [], "4": [5,6,7], "5": [1,2] }
  ```
  - Clave = día de la semana (1 lunes … 5 viernes).
  - Valor = **bloques de clase** (1–7, ver la tabla de `CLAUDE.md`) en los que el docente **sí** puede dar clase. No son horas del reloj: el bloque 1 es 15:00–15:50 y el 7 es 20:30–21:20.
  - Siempre se guardan las cinco claves; un día sin disponibilidad lleva `[]`. Los bloques van ordenados y sin repetir.
  - JSON válido: sin coma después del último elemento (`[1,2,3]`, no `[1,2,3,]`).
- **Materias que puede impartir** (`docente_materia`): solo materias activas. Se sugieren primero las de su área disciplinar, pero se puede elegir cualquiera activa.
- Contraseña temporal **generada por el sistema** en el campo del formulario (regla en `CLAUDE.md` § 4; reutiliza `Usuario::generarContrasenaTemporal()` del Plan 01).
- El límite de tres materias del docente de tiempo completo y la coincidencia con `horas_plaza` **no** se validan aquí: se revisan en el horario (Plan 07).
- Un docente no puede ser administrador con la misma cuenta.
- Baja lógica. Si el docente tiene clases en el horario del periodo vigente, se muestra **solo una advertencia** y se permite continuar.
- Campos únicos (número de empleado, correo, CURP, RFC) → error en la ventana emergente genérica.

## Capa Modelo
- `Docente`: `$table = 'docente'`, `$primaryKey = 'id_docente'`, `$incrementing = false`, `$timestamps = false`, cast `disponibilidad => array`.
  - Relaciones: `usuario()`, `areaDisciplinar()`, `estudios()` (`hasMany` DocenteEstudio), `materias()` (`belongsToMany` Materia, tabla `docente_materia`, claves `id_docente`/`id_materia`), `clases()` (`hasMany` DetalleHorarioGrupo, se crea en el Plan 07).
  - Métodos: `esTiempoCompleto()` (`horas_plaza === 40`), `estaDisponible(int $dia, int $bloque): bool`.
- `DocenteEstudio`: `$table = 'docente_estudio'`, `$timestamps = false`, constantes de grado (1 TSU, 2 Licenciatura, 3 Especialidad, 4 Maestría, 5 Doctorado) y accessor `grado_texto`.
- En el modelo de usuario: `docente()` (`hasOne`), scope `docentes()`.

## Capa Controlador
- `Admin\DocenteController`: `index`, `create`, `store`, `show`, `edit`, `update`, `baja`.
- `store` / `update` en **una transacción**: usuario → docente → estudios (en update: borrar y volver a insertar los estudios es aceptable a este nivel) → `materias()->sync($ids)`.
- `Admin\DisponibilidadDocenteController` (`edit`, `update`): guarda la matriz día × bloque.
- Form Requests:
  - `StoreDocenteRequest` / `UpdateDocenteRequest`: CURP/RFC con las regex del esquema, `horas_plaza` 2–40, `id_area_disciplinar` `exists`, `estudios` array con `estudios.*.descripcion` y `estudios.*.grado_estudio` 1–5, `materias` array con `exists:materia,id` y materia activa.
  - `UpdateDisponibilidadRequest`: `disponibilidad` array con claves 1–5; `disponibilidad.*` array (puede ir vacío); `disponibilidad.*.*` entero 1–7 `distinct`. Antes de guardar, completar los días faltantes con `[]` y ordenar los bloques.
- Ruta `admin/docentes/contrasena-temporal` (JSON) para el botón "Generar otra", declarada antes de las rutas con `{docente}`.
- `baja`: si el docente tiene clases en el periodo vigente y la petición no trae `confirmar=1`, regresa con la advertencia para que la vista pida confirmación.

## Capa Vista — solicitar interfaces
Pide mockups de: listado, registro, consulta, modificación, captura de disponibilidad y confirmación de baja (con advertencia). Con Alpine: filas dinámicas de estudios (agregar/quitar) y cuadrícula de disponibilidad (días × 7 bloques con casillas), usando la tabla de bloques de `config/horario.php`.

## Criterios de aceptación
- Alta, consulta, edición y baja de docentes con sus estudios y materias.
- La disponibilidad se guarda y se vuelve a mostrar marcada correctamente.
- Dar de baja a un docente con horario vigente muestra advertencia antes de confirmar.

## Decisiones tomadas
- Disponibilidad: JSON `{"día": [bloques disponibles]}` con días 1–5 y bloques 1–7.
- Contraseña temporal: la genera el sistema en el campo de texto del formulario.

## Preguntas abiertas (resolver en Fase 0)
1. ¿Se pueden elegir materias de otra área disciplinar o solo las de la suya?

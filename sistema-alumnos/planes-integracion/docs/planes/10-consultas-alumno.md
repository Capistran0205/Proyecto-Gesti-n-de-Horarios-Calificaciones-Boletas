# Plan 10 — Consultas del alumno (HU-12 y HU-13)

**Historias:**
- HU-12: Como alumno, quiero consultar mi horario.
- HU-13: Como alumno, quiero consultar mis calificaciones.

**Rol que opera:** Alumno (rol 3), solo lectura. **Depende de:** Planes 07 y 09.

## Tablas (lectura)
`detalle_grupo`, `grupo`, `horario_grupo`, `detalle_horario_grupo`, `materia`, `salon`, `usuario` (docente), `calificacion_alumno`.

## Reglas de negocio
- El alumno solo ve su propia información; su id sale de la sesión.
- **Horario:** el del grupo en que está inscrito en el periodo vigente. Si no tiene grupo, mensaje "Aún no tienes grupo asignado".
- **Calificaciones:** por materia del grupo vigente, bloques 1–3 y final.
  - Cada bloque se muestra redondeado; no aprobatoria = **5 en rojo** (`Calificacion::paraMostrar`).
  - Final = promedio de los tres bloques redondeado; si falta algún bloque, se muestra "—".
- Las materias se toman del horario del grupo (materias distintas), para que aparezcan aunque aún no tengan calificación.

## Capa Modelo
- Reutiliza modelos. Agrega en `Alumno`:
  - `grupoVigente()` (si no se hizo en el Plan 05).
  - `calificacionesDelPeriodo($idPeriodo)`: agrupadas por materia → `[id_materia => [1 => x, 2 => y, 3 => z]]`.

## Capa Controlador
- `Alumno\MiHorarioController@index`: matriz `[dia][bloque] => clase` con materia, docente y aula.
- `Alumno\MisCalificacionesController@index`: arreglo de filas `{materia, bloque1, bloque2, bloque3, final}` ya calculado con `App\Support\Calificacion`; la vista no hace cálculos.

## Capa Vista — solicitar interfaces
Pide mockups de: horario del alumno (cuadrícula semanal; en celular, lista por día) y tabla de calificaciones. Reutiliza el parcial de cuadrícula del Plan 08 si la estructura coincide.

## Criterios de aceptación
- El alumno ve su horario con materia, docente y aula.
- El alumno ve sus calificaciones por bloque y la final; las no aprobatorias aparecen como 5 en rojo.

## Preguntas abiertas (resolver en Fase 0)
1. ¿El alumno puede consultar calificaciones de semestres anteriores?
2. ¿Se muestra el nombre del docente en el horario del alumno?

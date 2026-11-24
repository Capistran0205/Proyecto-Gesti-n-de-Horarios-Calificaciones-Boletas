# Plan 08 — Consultas del docente (HU-09 y HU-10)

**Historias:**
- HU-09: Como docente, quiero consultar mi horario para saber cuándo y dónde imparto clase.
- HU-10: Como docente, quiero consultar los grupos de mi horario con sus respectivos alumnos.

**Rol que opera:** Docente (rol 2), solo lectura. **Depende de:** Plan 07.

## Tablas (lectura)
`detalle_horario_grupo`, `horario_grupo`, `grupo`, `materia`, `salon`, `detalle_grupo`, `alumno`, `usuario`.

## Reglas de negocio
- El docente solo ve **su propia** información del **periodo vigente**; el id del docente sale de la sesión, nunca de la URL.
- Un grupo solo es visible si el docente tiene al menos una clase en él.
- Solo se listan alumnos activos.

## Capa Modelo
- Reutiliza los modelos existentes. Agrega en `Docente`:
  - `clasesDelPeriodo($idPeriodo)`: clases con `materia`, `salon` y `horario.grupo` cargados (`with`) para evitar consultas N+1.
  - `gruposDelPeriodo($idPeriodo)`: grupos distintos donde imparte, con las materias que da en cada uno.

## Capa Controlador
- `Docente\MiHorarioController@index`: arma una matriz `[dia][bloque] => clase` para la vista.
- `Docente\MisGruposController`:
  - `index`: grupos del periodo vigente con materia(s) y número de alumnos.
  - `show(Grupo $grupo)`: lista de alumnos (matrícula, nombre completo) ordenada por apellido; `abort(403)` si el docente no imparte en ese grupo.

## Capa Vista — solicitar interfaces
Pide mockups de: horario del docente (cuadrícula semanal con materia, grupo y aula por celda; en celular, lista por día), lista de grupos y lista de alumnos de un grupo.

## Criterios de aceptación
- El docente ve su horario semanal con materia, grupo y aula.
- El docente ve sus grupos y los alumnos de cada uno; no puede abrir grupos ajenos cambiando la URL.

## Preguntas abiertas (resolver en Fase 0)
1. ¿El docente puede consultar periodos anteriores o solo el vigente?
2. ¿Se necesita imprimir o exportar la lista de alumnos?

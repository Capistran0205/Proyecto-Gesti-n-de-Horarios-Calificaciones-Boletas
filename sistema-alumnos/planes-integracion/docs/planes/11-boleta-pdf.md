# Plan 11 — Boleta final de semestre en PDF (HU-14)

**Historia:** Como alumno, quiero generar mi boleta final de semestre en PDF.
**Rol que opera:** Alumno (rol 3). **Depende de:** Planes 09 y 10.

## Tablas (lectura)
Las mismas del Plan 10.

## Reglas de negocio
- Es la boleta **del semestre del grupo** en el periodo seleccionado (por defecto el vigente).
- Muestra **solo la calificación final** de cada materia (promedio de los tres bloques, redondeado; no aprobatoria = 5, en rojo).
- **Decisión tomada:** la boleta está disponible cuando **todas las materias del grupo tienen los tres bloques capturados** (equivale a la "carga final cerrada", ya que la apertura/cierre de captura está fuera de alcance). Si falta alguna, se informa qué materias faltan y no se genera.
- Formato: el que comparta la escuela; si no hay, una propuesta del equipo basada en preparatorias de la DGB.

## Dependencia nueva
- `barryvdh/laravel-dompdf` (`composer require barryvdh/laravel-dompdf`). **Pide aprobación antes de instalarla**, y confirma que la versión es compatible con la de Laravel del proyecto.

## Capa Modelo
- Sin modelos nuevos. Agrega en `Alumno` un método `datosBoleta($idPeriodo): array` (o una clase `app/Support/Boleta.php` si queda muy largo) que devuelva: datos del alumno, grupo, periodo, especialidad, filas `{clave, materia, final}`, promedio general y la lista de materias incompletas.

## Capa Controlador
- `Alumno\BoletaController`:
  - `index`: vista previa en HTML con botón "Descargar PDF" (o aviso de materias incompletas).
  - `descargar`: `Pdf::loadView('alumno.boleta.pdf', $datos)->download("boleta_{$matricula}_{$periodo}.pdf")`; si está incompleta, regresa con el aviso.
- Envuelve la generación en `try-catch` y registra el error con `Log::error()`.

## Capa Vista — solicitar interfaces
Pide **el formato de boleta de la escuela** (o la propuesta del equipo) y el mockup de la pantalla previa. La plantilla del PDF (`alumno/boleta/pdf.blade.php`) usa CSS en línea y tablas simples (dompdf no soporta flexbox ni grid) y no extiende el layout web.

## Criterios de aceptación
- El alumno descarga un PDF con sus calificaciones finales del semestre.
- Si faltan calificaciones, el sistema dice cuáles y no genera la boleta.
- Un alumno no puede descargar la boleta de otro.

## Preguntas abiertas (resolver en Fase 0)
1. ¿Ya llegó el formato oficial de boleta de la escuela?
2. ¿La boleta incluye promedio general del semestre?
3. ¿Lleva logotipo, firma o sello? (Pedir los archivos de imagen.)

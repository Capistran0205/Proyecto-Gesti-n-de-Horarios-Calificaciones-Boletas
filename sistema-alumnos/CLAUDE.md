<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>

# CLAUDE.md — Sistema de control escolar · Prep. Antonio María de Rivera


## 1. Rol del agente

Actúa como un **Desarrollador de Software de Nivel Intermedio** enfocado en la entrega de código funcional, limpio y legible.

**Objetivo:** escribir código que funcione a la primera, resuelva el problema planteado y use buenas prácticas estándar de la industria, evitando la complejidad innecesaria (sobreingeniería).

### Reglas de generación de código

1. **Funcionalidad primero.** El código debe ser completo y ejecutable. Prohibido usar marcadores de posición (`// poner lógica aquí`, `...`, `TODO: implementar`).
2. **Nivel intermedio.** Estructuras de control claras, manejo de errores básico/intermedio (`try-catch`, validaciones de entrada), modularización en funciones y estructuras de datos adecuadas (arrays, colecciones, mapas). Evita patrones de diseño abstractos (repositorios, interfaces genéricas, fábricas, eventos/listeners) y microoptimizaciones, salvo que se pidan explícitamente.
3. **Legibilidad.** Variables y funciones con nombres descriptivos en español, coherentes con el esquema de BD. Comentarios breves solo donde haga falta contexto, no en cada línea.
4. **Explicación concisa.** Después de cada entrega de código, un resumen de **máximo 3 puntos clave** sobre cómo funciona o cómo ejecutarlo/probarlo.

## 2. Stack y entorno

- **Backend:** Laravel (confirma la versión en `composer.json` antes de generar código y usa la sintaxis de esa versión).
- **Frontend:** Blade + Alpine.js. Diseño responsivo: escritorio, tableta y celular.
- **Base de datos:** PostgreSQL **dentro de un contenedor Docker**.
  - Obtén el nombre del servicio, la base y el usuario de `docker-compose.yml` y `.env`. Nunca escribas credenciales en el código ni en los planes.
  - Consulta la BD con: `docker compose exec <servicio_db> psql -U <usuario> -d <base>`.
  - Si Laravel corre en el host, `DB_HOST` apunta al puerto publicado del contenedor; si corre en otro contenedor, apunta al nombre del servicio. Verifica cuál es el caso antes de ejecutar `php artisan`.
- **Fuente de verdad del esquema:** el script `esquema_preparatoria.sql` (tablas en singular, `snake_case`, catálogos con `CHECK` numérico).
  - **No crees ni modifiques migraciones que alteren el esquema** sin aprobación. Si un módulo necesita un cambio de esquema, propón el `ALTER`/`CREATE` en SQL, explica el porqué y **espera aprobación**.
  - Si el repo ya maneja el esquema con migraciones de Laravel, respeta ese mecanismo y avísalo en la Fase 0.
- **Ya implementado:** login, redirección y flujo según el rol de las credenciales. **Reutiliza** el modelo de usuario, el guard y el middleware de rol existentes; no los reescribas ni dupliques.

## 3. Flujo obligatorio por módulo (con aprobación por capa)

Cada módulo tiene su plan en `docs/planes/`. Se integra **una capa MVC a la vez** y **no se avanza sin aprobación explícita** del usuario ("aprobado", "continúa" o equivalente).

| Fase | Qué hace el agente | Termina con |
|---|---|---|
| **0. Reconocimiento** | Lee el plan del módulo y el código relacionado. Lista los archivos que creará o modificará por capa y las preguntas abiertas del plan. | ⛔ Espera aprobación del plan |
| **1. Modelo** | Modelos Eloquent, relaciones, casts, constantes de catálogo, scopes y seeders (si el plan lo indica). Verifica con `php artisan tinker`. | ⛔ "¿Apruebas la capa Modelo?" |
| **2. Controlador** | Form Requests, controladores y rutas con el middleware de rol. Verifica con `php artisan route:list --name=<prefijo>`. | ⛔ "¿Apruebas la capa Controlador?" |
| **3. Vista** | **Antes de escribir cualquier archivo Blade, solicita las interfaces** (mockups, capturas o descripción de pantallas) de ese módulo. Espera a recibirlas y genera las vistas conforme a ellas. | ⛔ "¿Apruebas la capa Vista?" |
| **4. Cierre** | Lista de pruebas manuales del módulo, criterios de aceptación cubiertos y mensaje de commit sugerido. | ✅ Módulo terminado |

Reglas de los gates:
- No adelantes archivos de la capa siguiente "para ahorrar tiempo".
- Si el usuario pide cambios, aplícalos y vuelve a pedir aprobación **de la misma capa**.
- Si una decisión no está en el plan ni en este archivo, **pregunta** antes de asumir.
- No hagas `git commit` ni `git push` a menos que el usuario lo pida.

## 4. Convenciones de Laravel para este esquema

### Modelos
- Declara siempre `protected $table` (las tablas están en singular: `usuario`, `docente`, `materia`…).
- Tablas con `fecha_registro` y `fecha_modificacion`:
  ```php
  const CREATED_AT = 'fecha_registro';
  const UPDATED_AT = 'fecha_modificacion';
  ```
  Tablas que solo tienen `fecha_registro` (`especialidad`, `area_disciplinar`, `salon`): `const UPDATED_AT = null;`. Tablas sin fechas: `public $timestamps = false;`.
- Subtipos de usuario (`administrador`, `docente`, `alumno`, `super_usuario`): la PK es la FK a `usuario.id` → `protected $primaryKey = 'id_docente'`, `public $incrementing = false;`.
- Catálogos numéricos como constantes en el modelo (`Usuario::ROL_ADMINISTRADOR = 1`, `Usuario::ESTADO_ACTIVO = 1`, `Materia::TIPO_TRONCO_COMUN = 1`…) y un accessor para la etiqueta legible. No uses enums de PHP salvo que el repo ya los use.
- `disponibilidad` (JSONB) → `protected $casts = ['disponibilidad' => 'array'];`.

### Altas, cambios y bajas
- Alta de un usuario con subtipo = **una transacción** (`DB::transaction`) que inserta en `usuario` y en la tabla del subtipo.
- **Baja lógica siempre** (`estado = 0`). Nunca `delete()` sobre usuarios ni materias.
- **Contraseña temporal (decisión del equipo, aplica a administradores, docentes y alumnos):** el sistema la **genera** y la coloca en el campo de texto del formulario de alta.
  - Se genera en el servidor al abrir el formulario (`create`) con un método reutilizable, p. ej. `Usuario::generarContrasenaTemporal(int $longitud = 10)`: letras mayúsculas, minúsculas y dígitos, sin caracteres ambiguos (`0 O o 1 l I`).
  - El campo es visible (tipo texto) y trae un botón "Generar otra" (Alpine + endpoint `GET …/contrasena-temporal` que devuelve JSON) para que quien da de alta pueda copiarla y entregarla.
  - El servidor la valida otra vez (`required|string|min:8`), la guarda con `Hash::make()` y marca `cambio_contrasena_ini = true`. Nunca se guarda ni se registra en logs en texto plano.
  - El modal de éxito del alta la muestra una última vez para que se pueda entregar al usuario.
- `fecha_modificacion` la actualiza un trigger de la BD; no la asignes a mano.

### Validación y errores
- Un Form Request por acción (`StoreDocenteRequest`, `UpdateDocenteRequest`) con mensajes en español.
- `unique` con `ignore` en actualizaciones. Las expresiones regulares de CURP, RFC y NSS deben ser **las mismas** que los `CHECK` del esquema.
- Captura `QueryException` en las operaciones de escritura: registra el detalle con `Log::error()` y devuelve un mensaje genérico en sesión. La interfaz muestra los errores en **una ventana emergente genérica** (decisión del equipo).

### Rutas y controladores
- Agrupa por rol con el middleware existente: `superusuario/…`, `admin/…`, `docente/…`, `alumno/…`.
- Controladores por rol: `App\Http\Controllers\Admin\DocenteController`. Nombres de ruta: `admin.docentes.index`.
- Listados con paginación (15) y búsqueda simple por nombre o número de empleado/matrícula.
- Si una lógica se reutiliza en más de un controlador (p. ej., validar choques de horario), extráela a **una clase de servicio sencilla** en `app/Services`. Fuera de ese caso, mantenla en el controlador o en el modelo.

### Vistas
- Extiende el layout que ya usa el login. Alpine.js para modales, confirmaciones de baja y campos condicionales.
- Mensajes de éxito y error con el mismo componente de modal genérico en todo el sistema.

## 5. Reglas de dominio globales

- **Roles:** 0 SuperUsuario (único), 1 Administrador, 2 Docente, 3 Alumno. Un docente no puede ser administrador con la misma cuenta.
- **Acceso:** número de empleado (docentes y administradores) o matrícula (alumnos) en `usuario.usuario`; el correo es obligatorio para todos.
- **Periodo:** semestral. Solo modalidad escolarizada y turno vespertino único (15:00–21:20).
- **Bloques horarios** (`dia_semana` 1 = lunes … 5 = viernes):

  | Bloque | Horario | | Bloque | Horario |
  |---|---|---|---|---|
  | 1 | 15:00–15:50 | | 5 | 18:40–19:30 |
  | 2 | 15:50–16:40 | | *Descanso* | 19:30–19:40 |
  | 3 | 16:40–17:30 | | 6 | 19:40–20:30 |
  | *Descanso* | 17:30–17:50 | | 7 | 20:30–21:20 |
  | 4 | 17:50–18:40 | | | |

  Define esta tabla **una sola vez** (p. ej., `config/horario.php`) y reutilízala en todas las vistas de horario.
- **Calificaciones:** escala 5–10, mínimo aprobatorio 6, tres bloques (parciales) por semestre, final = promedio de los tres. Redondeo: decimal ≥ .5 sube, < .5 baja. Una calificación no aprobatoria se muestra como **5 en rojo**.
- **Grupos:** tope de 40 alumnos; solo el SuperUsuario puede subir `capacidad_max`.

## 6. Fuera de alcance (no implementar)

Modalidad y turno como criterio, apertura/cierre de captura de calificaciones, repites/regularizaciones, reprobación, materias pendientes, alumno irregular, asistencias, validación de la DGB, bitácora de cambios/auditoría, cifrado de CURP/RFC/NSS, calendario escolar, tiempos de modificación de horarios, migración de datos, hospedaje y respaldos.

## 7. Planes por módulo

Índice y orden de integración: [`docs/planes/00-indice.md`](docs/planes/00-indice.md).
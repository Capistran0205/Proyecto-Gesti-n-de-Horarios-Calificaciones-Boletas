# Estado del proyecto — Paso 0 (reconocimiento)

Fecha: 2026-11-23. Este documento no incluye credenciales; se consultan en `.env` y `PostgreSQL/docker-compose.yml`.

## 1. Stack detectado

| Elemento | Valor |
|---|---|
| Laravel | `^13.17` en `composer.json` (instalado 13.38.0) |
| PHP | `^8.3` en `composer.json` (CLI local 8.5.12) |
| Paquetes | `laravel/tinker`, `laravel/pail`, `laravel/pao`, `laravel/pint`, `phpunit` 12. **No** está `laravel/boost` (el bloque de `CLAUDE.md` que lo pide queda sin ejecutar hasta que se decida) |
| Frontend | Blade + Alpine.js 3 (`resources/js/app.js`, cargado con `@vite`). Tailwind 4 está en `package.json`, pero las vistas usan `public/css/portal.css` (45 líneas) |
| Base de datos | PostgreSQL 16.6 en Docker. Servicio `db`, contenedor `portal_pg`, puerto publicado `5433` → `5432`. Base `portal_escolar`, usuario `portal` |
| Laravel ↔ BD | Laravel corre **en el host** (`DB_HOST=127.0.0.1`, `DB_PORT=5433`). Consulta: `docker compose exec db psql -U portal -d portal_escolar` (desde `PostgreSQL/`) |
| Correo | SMTP SendGrid (recuperación de contraseña) |
| Sesión / caché / cola | Driver `database` |

**No se pudo consultar la BD:** el daemon de Docker no estaba en ejecución durante el reconocimiento. Lo que se dice de las tablas sale de `TablasLogin.sql`, no de la BD en vivo.

## 2. Cómo está resuelto el login (HU-01)

- **Modelo:** `app/Models/User.php`
  - Tabla `usuario`; `CREATED_AT = fecha_registro`, `UPDATED_AT = fecha_modificacion`.
  - Columna de contraseña `contrasena` (cast `hashed`, `getAuthPassword()` y `getAuthPasswordName()` sobreescritos).
  - Catálogo `ROLES` (0–3) y rutas de panel por rol (`RUTAS_PANEL`).
  - Métodos `nombreRol()`, `rutaPanel()`, `nombreCompleto()`. `getRememberTokenName()` devuelve `''`.
- **Guard:** `web` (sesión) con provider `eloquent` → `User`. Un solo guard.
- **Middleware de rol:** alias `rol` registrado en `bootstrap/app.php` → `App\Http\Middleware\RolMiddleware`. Se usa como `->middleware('rol:1')`; compara el rol como texto y responde 403 si no coincide.
- **Login:** `AuthController`
  - Acepta usuario o correo; solo `estado = 1`.
  - Bloqueo de 5 intentos / 300 s con `RateLimiter`.
  - Mensaje de error genérico.
- **Recuperación:** `PasswordRecoveryController` usa el broker `users` con la columna `correo`. Al restablecer, borra las sesiones del usuario en `sessions`.
- **Redirección por rol:** `/panel` redirige a `panel.superusuario`, `panel.administrador`, `panel.docente` o `panel.alumno`. Todas muestran la misma vista `panel/inicio.blade.php`.
- **Layouts y componentes:**
  - `layouts/auth.blade.php`: tarjeta centrada con logo, para login y recuperación.
  - `auth/estado.blade.php`: vista genérica de estado con alerta y botones.
  - **No existe** layout de panel ni componente de modal genérico. `panel/inicio.blade.php` es un HTML completo con su propio encabezado y menú; los botones "Entrar" están deshabilitados.

## 3. Mecanismo del esquema

- **No se usa migraciones de Laravel para el esquema de negocio.** Las tablas se crean con scripts SQL montados en `PostgreSQL/db/init/` (`docker-entrypoint-initdb.d`).
- Las 3 migraciones del repo son las de Laravel por defecto: `password_reset_tokens`, `sessions`, `cache` y `jobs`.
- Esto respeta la regla de `CLAUDE.md`: el esquema se cambia con SQL aprobado, no con migraciones.

## 4. Diferencias con `esquema_preparatoria.sql`

1. **El archivo `esquema_preparatoria.sql` no está en el repositorio.** Solo existe `PostgreSQL/db/init/TablasLogin.sql`. Hace falta que el equipo lo agregue, porque es la fuente de verdad según `CLAUDE.md`.
2. **Tablas existentes en `TablasLogin.sql`:** `usuario`, `superusuario`, `administrador`, `docente`, `alumno`.
3. **Tablas que aún no existen** (según los planes): `especialidad`, `area_disciplinar`, `salon`, `materia`, grupos, horarios, calificaciones, etc.
4. **Columna que falta:** `usuario.cambio_contrasena_ini` (la exige la regla de contraseña temporal).
5. **Trigger que falta:** `TablasLogin.sql` no define el trigger que actualiza `fecha_modificacion`. `CLAUDE.md` asume que existe.
6. **`alumno.id_area`** no tiene FK, porque `area_disciplinar` todavía no existe.
7. **Modelos que faltan:** solo existe `User`. No hay modelos de `Administrador`, `Docente`, `Alumno` ni `SuperUsuario`.
8. **`User` no cumple todavía las convenciones de `CLAUDE.md`:**
   - Faltan constantes `ROL_*` y `ESTADO_*`.
   - Falta `generarContrasenaTemporal()`.
   - Los roles están en un arreglo `ROLES`, no en constantes.
   - Se reutiliza tal cual y se le agrega lo necesario.

## 5. Convenciones detectadas

- Código y mensajes en español; comentarios cortos con referencias a reglas de negocio (RN-08, RN-13, RN-14, RN-15, RN-73…).
- Rutas planas (`/superusuario`, `/administrador`, …) con nombres `panel.*`. Aún no existen los prefijos `admin/…` ni nombres `admin.docentes.index`.
- Controladores en `App\Http\Controllers` sin subcarpeta por rol.
- Validación en línea en los controladores; todavía no hay Form Requests.
- `public/css/portal.css` y `public/img/logoprepa.png` son los recursos de estilo y logo.
- `.env.example` apunta a SQLite y no refleja el `.env` real (PostgreSQL, SendGrid).
- Pruebas: solo los ejemplos por defecto en `tests/`.

## 6. Observaciones a decidir antes del Módulo 1

1. ¿Se agrega `esquema_preparatoria.sql` al repo (y se ejecuta en `db/init`)? ¿O se aplica el `ALTER` de `cambio_contrasena_ini` y el trigger aparte?
2. Si la BD ya está creada, los scripts de `db/init` no se vuelven a ejecutar; los cambios requieren `ALTER` manual o recrear el volumen `pgdata`.
3. ¿Se actualiza `.env.example` para que refleje PostgreSQL?
4. ¿Se instala `laravel/boost`? No se ejecutó porque cambia `composer.json`.
5. Para el modal genérico y el layout de panel: se definirán en la capa Vista del Módulo 1, cuando se reciban los mockups.

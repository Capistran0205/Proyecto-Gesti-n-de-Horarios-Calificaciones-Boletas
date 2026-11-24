# Plan 01 — Gestión de administradores (HU-02)

**Historia:** Como SuperUsuario, quiero registrar, consultar, editar y dar de baja administradores para controlar quién opera el sistema.
**Rol que opera:** SuperUsuario (rol 0). **Depende de:** Paso 0.

## Tablas
`usuario` (rol = 1) + `administrador` (`id_admin`, `curp`, `rfc`).

## Reglas de negocio
- Solo el SuperUsuario gestiona administradores; los administradores no gestionan a otros administradores.
- Campos: número de empleado (numérico, va en `usuario.usuario`), nombre, apellido paterno, apellido materno (opcional), correo (obligatorio), CURP, RFC, contraseña temporal.
- Campos únicos: número de empleado, correo, CURP, RFC. Si se repiten, error en la ventana emergente genérica.
- Contraseña temporal **generada por el sistema** en el campo del formulario (mínimo 8 caracteres), con `cambio_contrasena_ini = true`. Ver la regla completa en `CLAUDE.md` § 4.
- Baja lógica (`usuario.estado = 0`). Un administrador dado de baja no puede iniciar sesión (el login ya verifica la cuenta activa).
- El propio administrador restablece su contraseña desde su apartado (ya cubierto por HU-01; no se repite aquí).

## Capa Modelo
- `app/Models/Administrador.php`: `$table = 'administrador'`, `$primaryKey = 'id_admin'`, `$incrementing = false`, `$timestamps = false`, `$fillable = ['id_admin', 'curp', 'rfc']`, relación `usuario()` (`belongsTo` Usuario, `id_admin`).
- En el modelo de usuario existente: relación `administrador()` (`hasOne`), constantes de rol y estado si no existen, scope `scopeAdministradores()` y `scopeActivos()`, accessor `nombre_completo`.
- **Verificación en tinker:** crear un usuario rol 1 + administrador dentro de una transacción y leerlo con `Usuario::administradores()->with('administrador')->first()`. Revertir con `DB::rollBack()` al terminar.

## Capa Controlador
| Método | Ruta | Nombre | Acción |
|---|---|---|---|
| GET | `/superusuario/administradores` | `superusuario.administradores.index` | Listado paginado con búsqueda y filtro activo/baja |
| GET | `/superusuario/administradores/crear` | `.create` | Formulario |
| POST | `/superusuario/administradores` | `.store` | Alta (transacción usuario + administrador) |
| GET | `/superusuario/administradores/{usuario}` | `.show` | Consulta |
| GET | `/superusuario/administradores/{usuario}/editar` | `.edit` | Formulario |
| PUT | `/superusuario/administradores/{usuario}` | `.update` | Edición (transacción) |
| PATCH | `/superusuario/administradores/{usuario}/baja` | `.baja` | `estado = 0` |
| GET | `/superusuario/administradores/contrasena-temporal` | `.contrasena` | JSON con una contraseña nueva (botón "Generar otra"); declárala **antes** de las rutas con `{usuario}` |

- `SuperUsuario\AdministradorController` con middleware de rol 0.
- `StoreAdministradorRequest` / `UpdateAdministradorRequest`: número de empleado `digits_between`, correo `email`, CURP/RFC con las regex del esquema (en mayúsculas: convertir con `strtoupper` en `prepareForValidation`), `unique` con `ignore` en update. La contraseña (`required|string|min:8`) solo va en el alta; `create` entrega a la vista la contraseña ya generada. Este primer módulo crea el método reutilizable `Usuario::generarContrasenaTemporal()`.
- Validar que el `{usuario}` recibido sea rol 1; si no, `abort(404)`.

## Capa Vista — solicitar interfaces
Pide los mockups de: listado, registro, consulta, edición y confirmación de baja. Luego genera `resources/views/superusuario/administradores/{index,create,show,edit}.blade.php` y, si aplica, un parcial `_form.blade.php` compartido entre alta y edición.

## Criterios de aceptación
- El SuperUsuario registra, consulta, edita y da de baja administradores.
- Un administrador nuevo entra con su número de empleado y se le obliga a cambiar la contraseña.
- Un administrador dado de baja no puede entrar.
- Un administrador o docente que intente abrir estas rutas recibe 403 (o la redirección que ya maneje el middleware).

## Decisiones tomadas
- Contraseña temporal: la genera el sistema en el campo de texto del formulario (mínimo 8 caracteres).

## Preguntas abiertas (resolver en Fase 0)
1. ¿Se permite **reactivar** a un administrador dado de baja?

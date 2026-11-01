<!DOCTYPE html>
<html lang="es">

<body style="margin:0;padding:24px;background:#f3f5f8;font-family:'Segoe UI',Arial,sans-serif;color:#1c2430;">
    <div style="max-width:620px;margin:0 auto;padding:32px;background:#ffffff;border-radius:10px;">
        <!--<img src="{{ asset('img/logoprepa.png') }}" alt="Escuela de Bachilleres Antonio María de Rivera"
             style="width:100%;height:auto;margin-bottom:24px;">-->

        <p style="font-size:18px;line-height:1.6;">
            Hola, recibimos una solicitud para restablecer la contraseña del usuario
            <strong>{{ $usuario }}</strong>. Haz clic en el botón para crear una nueva contraseña.
        </p>

        <p>
            <a href="{{ $url }}"
                style="display:inline-block;padding:14px 28px;background:#0f6cbf;color:#ffffff;font-size:18px;text-decoration:none;border-radius:10px;">
                Crear nueva contraseña
            </a>
        </p>

        <p style="color:#5b6573;font-size:14px;line-height:1.6;">
            Si no fuiste tú, ignora este mensaje: tu contraseña actual no cambiará. El enlace tiene una vigencia
            limitada.
        </p>
    </div>
</body>

</html>

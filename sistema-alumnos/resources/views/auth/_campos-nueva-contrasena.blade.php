{{-- Campos de nueva contraseña, compartidos por el restablecimiento y el cambio obligatorio --}}
<input class="campo @if ($errors->any()) invalido @endif" type="password" name="contrasena"
       placeholder="Nueva contraseña" autocomplete="new-password" autofocus>

<input class="campo @if ($errors->any()) invalido @endif" type="password" name="contrasena_confirmation"
       placeholder="Confirmar nueva contraseña" autocomplete="new-password">

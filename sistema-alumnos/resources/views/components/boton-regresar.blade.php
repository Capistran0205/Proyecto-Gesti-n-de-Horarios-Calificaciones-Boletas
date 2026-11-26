{{-- Botón para regresar a la sección anterior. Por defecto, al menú principal del rol del usuario. --}}
@props(['ruta' => null, 'texto' => 'Regresar'])

<a href="{{ $ruta ?? route(auth()->user()->rutaPanel()) }}" {{ $attributes->merge(['class' => 'btn btn-borde']) }}>← {{ $texto }}</a>

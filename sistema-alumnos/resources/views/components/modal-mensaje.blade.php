{{-- Ventana emergente genérica de éxito / error para todo el sistema --}}
@props(['tipo' => 'exito', 'titulo', 'mensaje' => null, 'intro' => null, 'items' => [], 'contrasena' => null])

<div x-data="{ abierto: true, copiado: false }" x-show="abierto" x-cloak
     @keydown.escape.window="abierto = false" class="modal-fondo" role="dialog" aria-modal="true">
    <div class="modal modal-ancho" @click.outside="abierto = false">
        <div class="modal-cabecera">
            <span class="modal-icono {{ $tipo }}">{{ $tipo === 'error' ? '!' : '✓' }}</span>
            <h2>{{ $titulo }}</h2>
        </div>

        @if ($intro && count($items) > 0)
            <p>{{ $intro }}</p>
        @endif

        @if (count($items) > 0)
            <div class="aviso error">
                <ul>
                    @foreach ($items as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($mensaje)
            <p>{{ $mensaje }}</p>
        @endif

        @if ($contrasena)
            <p class="nota-pequena">Contraseña temporal. Entrégasela al administrador: no se volverá a mostrar.</p>
            <div class="contrasena-entregada">
                <code>{{ $contrasena }}</code>
                <button type="button" class="btn btn-borde btn-mini"
                        @click="navigator.clipboard.writeText(@js($contrasena)); copiado = true"
                        x-text="copiado ? 'Copiada' : 'Copiar'">Copiar</button>
            </div>
        @endif

        <div class="modal-acciones">
            <button type="button" class="btn btn-primario" @click="abierto = false" autofocus>Entendido</button>
        </div>
    </div>
</div>

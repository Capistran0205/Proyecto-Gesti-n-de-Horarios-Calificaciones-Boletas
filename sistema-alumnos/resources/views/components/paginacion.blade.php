{{-- Paginación numerada: Anterior · 1 2 3 · Siguiente. Uso: <x-paginacion :paginador="$materias" /> --}}
@props(['paginador'])

@php
    $actual = $paginador->currentPage();
    $ultima = max($paginador->lastPage(), 1);
    $paginas = $paginador->getUrlRange(max(1, $actual - 2), min($ultima, $actual + 2));
@endphp

<nav class="paginacion" aria-label="Paginación">
    <a href="{{ $paginador->previousPageUrl() ?? '#' }}" class="btn btn-borde {{ $paginador->onFirstPage() ? 'inactivo' : '' }}">Anterior</a>

    @foreach ($paginas as $numero => $url)
        <a href="{{ $url }}" class="btn {{ $numero === $actual ? 'actual' : 'btn-borde' }}" @if ($numero === $actual) aria-current="page" @endif>{{ $numero }}</a>
    @endforeach

    <a href="{{ $paginador->nextPageUrl() ?? '#' }}" class="btn btn-borde {{ $paginador->hasMorePages() ? '' : 'inactivo' }}">Siguiente</a>
</nav>

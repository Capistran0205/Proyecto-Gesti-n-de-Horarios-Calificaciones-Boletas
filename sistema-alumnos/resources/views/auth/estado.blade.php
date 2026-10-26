@extends('layouts.auth')

@section('titulo', $titulo)

@section('contenido')
    <h1>{{ $titulo }}</h1>

    @isset($mensaje)
        <div class="alerta {{ $tipo === 'error' ? 'alerta-error' : 'alerta-exito' }}" role="alert">{{ $mensaje }}</div>
    @endisset

    @foreach ($textos ?? [] as $texto)
        <p class="texto">{{ $texto }}</p>
    @endforeach

    @foreach ($botones as $boton)
        <a class="boton {{ $boton['principal'] ? 'boton-principal' : 'boton-borde' }}" href="{{ $boton['url'] }}">{{ $boton['texto'] }}</a>
    @endforeach
@endsection
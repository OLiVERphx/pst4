@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
   {{-- <h1>Dashboard</h1> --}}
@stop

@section('content')
    <div class="text-center pt-5">
{{-- <p>Bienvenid@ al Panel de Administracion de ZAMBRANO'S Cell.</p> --}}
        <br>
        <img
            src="{{asset('/assets/img/OIP.png')}}" alt="OIP" class="img-fluid"
            style=" width: 900px; "

        >
    </div>
@stop

@section('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@stop

@section('js')
{{--    <script> console.log("Hi, I'm using the Laravel-AdminLTE package!"); </script>--}}
@stop

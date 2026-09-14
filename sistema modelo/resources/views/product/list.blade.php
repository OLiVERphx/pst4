@extends('adminlte::page')

@section('css')
@stop

@section('content_header')
    <h1>
        Listado de Productos -
        <a class="btn btn-primary" href="{{route('product.create')}}">Crear Nuevo Producto</a>
    </h1>
@stop

@section('content')
    <div class="card card-overflow-auto">
        <div class="card-body">
            @livewire('datatables.products-table')
        </div>
    </div>
@stop

@section('js')
    <script>
        $(document).ready(function(){

        });
    </script>
@stop

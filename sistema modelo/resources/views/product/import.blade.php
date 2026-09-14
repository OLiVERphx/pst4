@extends('adminlte::page')

@section('css')
@stop

@section('content_header')
    <h1>
        Productos
    </h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{route('product.import')}}" method="post" enctype="multipart/form-data">
                {{ csrf_field() }}
                <input type="file" name="file" id="file">
                <input type="submit" value="Importar" class="btn btn-primary">
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <p>
                la estructura del csv de importacion es la siguiente:
            </p>
            <table class="table table-bordered text-center">
                <tr>
                    <th>codigo</th>
                    <th>nombre</th>
                    <th>cantidad</th>
                    <th>cantidad_minima</th>
                    <th>cantidad_maxima</th>
                    <th>costo</th>
                    <th>precio</th>
                    <th>marca</th>
                    <th>proveedor</th>
                    <th>categoria</th>
                </tr>
            </table>
            <hr>
            <p>
                El proceso en cuestion es crear un archivo en un software como excel o libre office,
                con la cabecera antes descrita, llenar la informacion requerida y subirla,
                tomar en cuenta siempre mantener la extructura antes mencionada, con los respectivos nombres de columnas como cabeceras.
            </p>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(document).ready(function(){

        });
    </script>
@stop

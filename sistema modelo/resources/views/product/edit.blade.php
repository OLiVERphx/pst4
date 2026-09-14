@extends('adminlte::page')

@section('css')
@stop

@section('content_header')
    <h1>
        Editar o Crear Productos
    </h1>
@stop

@section('content')
    <div class="card card-overflow-auto">
        <div class="card-body">
            <form action="{{Route('product.store')}}" method="post" >
                <div class="row">

                    {{ csrf_field() }}
                    @if (isset($product))
                        <input type="hidden" name="id" id="id" value='{{$product->id}}'>
                    @endif
                    @csrf

                    <div class="form-group col-md-2">
                        <label for="product_key">Codigo</label>
                        <input type="text" class="form-control" id="product_key" name="product_key" @if(isset($product)) value='{{$product->product_key}}' @endif required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="description">Descripcion</label>
                        <input type="text" class="form-control" id="description" name="description" @if(isset($product)) value='{{$product->description}}' @endif required>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="qty">Cantidad</label>
                        <input type="number" class="form-control" id="qty" name="qty" min="0" @if(isset($product)) value='{{$product->qty}}' @endif required>
                    </div>

                    <div class="form-group col-md-2">
                        <label for="min_qty">Cantidad Minima</label>
                        <input type="number" class="form-control" id="min_qty" name="min_qty"  min="0" @if(isset($product)) value='{{$product->min_qty}}' @endif >
                    </div>
                    <div class="form-group col-md-2">
                        <label for="max_qty">Cantidad Maxima</label>
                        <input type="number" class="form-control" id="max_qty" name="max_qty" min="0" @if(isset($product)) value='{{$product->max_qty}}' @endif >
                    </div>
                    <div class="form-group col-md-2">
                        <label for="cost">Costo</label>
                        <input type="number" class="form-control" id="cost" name="cost" min="0" step="0.01" @if(isset($product)) value='{{$product->cost}}' @endif>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="price">Precio</label>
                        <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" @if(isset($product)) value='{{$product->price}}' @endif>
                    </div>

                    <div class="form-group col-md-2">
                        <label for="brand_id">Marca</label>
                        <select name="brand_id" id="brand_id" class="form-control">
                            @foreach ($brands as $brand)
                                <option value="{{$brand->id}}" @if(isset($product) && $brand->id == $product->brand_id) selected @endif >
                                    {{$brand->name}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="vendor_id">Proveedor</label>
                        <select name="vendor_id" id="vendor_id" class="form-control">
                            @foreach ($vendors as $vendor)
                                <option value="{{$vendor->id}}" @if(isset($product) && $vendor->id == $product->vendor_id) selected @endif >
                                    {{$vendor->name}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="category_id">Categoria</label>
                        <select name="category_id" id="category_id" class="form-control">
                            @foreach ($categories as $category)
                                <option value="{{$category->id}}" @if(isset($product) && $category->id == $product->category_id) selected @endif >
                                    {{$category->name}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <p>&nbsp;</p>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @if (isset($product))
        <div class="card card-overflow-auto">
            <div class="card-header">
                Historial de Cambios del producto
            </div>
            <div class="card-body">
                @livewire('datatables.history-products-table', ['id' => $product->id])
            </div>
        </div>
    @endif

@stop

@section('js')
    <script>
        $(document).ready(function(){

        });

        function confirmDelete(itemId) {
            return confirm("¿Estás seguro de que quieres eliminar la ruta: " + itemId + "?");
        }
    </script>
@stop

@extends('layouts.web')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="col-span-2 bg-white p-4 rounded shadow">
        <div class="h-80 bg-gray-100 flex items-center justify-center">📱</div>
        <h1 class="text-2xl font-bold mt-4">{{ $product->nombre }}</h1>
        <div class="text-sm text-gray-500">{{ $product->brand->nombre ?? '' }} · {{ $product->category->nombre ?? '' }}</div>
        <div class="mt-4">{{ $product->descripcion }}</div>
    </div>
    <div class="bg-white p-4 rounded shadow">
        <div class="text-2xl font-bold">${{ number_format($product->precio_detal, 2) }}</div>
        <div class="mt-2">Stock: {{ $product->stock }}</div>
        <div class="mt-4">
            <div x-data="{ qty:1 }">
                <input type="number" x-model="qty" min="1" class="border px-2 py-1 w-20">
                <button @click.prevent="Alpine.store('cart').add({ id: {{ $product->id }}, nombre: '{{ addslashes($product->nombre) }}', precio_detal: {{ $product->precio_detal }} }, qty)" class="ml-2 px-3 py-2 bg-green-600 text-white rounded">Add to cart</button>
            </div>
        </div>
    </div>
</div>
@endsection
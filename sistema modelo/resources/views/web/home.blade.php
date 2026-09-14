@extends('layouts.web')

@section('content')
<div class="space-y-8">
    <section class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg p-8">
        <h1 class="text-3xl font-bold">Smartphone World</h1>
        <p class="mt-2">Find the best phones at great prices.</p>
    </section>

    <section>
        <h2 class="text-xl font-semibold">Featured</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
            @foreach($featured as $p)
                <a href="{{ route('product.show', $p->slug ?? $p->id) }}" class="block bg-white rounded shadow p-4">
                    <div class="h-40 bg-gray-100 flex items-center justify-center">📱</div>
                    <h3 class="mt-2 font-semibold">{{ $p->nombre }}</h3>
                    <div class="text-sm text-gray-500">{{ $p->brand->nombre ?? '' }}</div>
                    <div class="mt-1 font-bold">${{ number_format($p->precio_detal, 2) }}</div>
                </a>
            @endforeach
        </div>
    </section>
</div>
@endsection
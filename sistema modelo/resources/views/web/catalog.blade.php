@extends('layouts.web')

@section('content')
<div>
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold">Catalog</h1>
        <form method="GET" action="{{ route('catalog') }}">
            <input type="search" name="search" placeholder="Search..." value="{{ request('search') }}" class="border px-2 py-1 rounded">
            <button class="ml-2 px-3 py-1 bg-blue-600 text-white rounded">Search</button>
        </form>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
        @foreach($products as $p)
            <div class="bg-white rounded shadow p-4">
                <a href="{{ route('product.show', $p->slug ?? $p->id) }}">
                    <div class="h-36 bg-gray-100 flex items-center justify-center">📱</div>
                    <h3 class="mt-2 font-semibold">{{ $p->nombre }}</h3>
                </a>
                <div class="mt-1 font-bold">${{ number_format($p->precio_detal, 2) }}</div>
            </div>
        @endforeach
    </div>

    <div class="mt-6">{{ $products->links() }}</div>
</div>
@endsection
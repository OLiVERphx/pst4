@extends('layouts.web')

@section('content')
<div class="bg-white p-4 rounded shadow">
    <h2 class="text-xl font-bold">Shopping Cart</h2>
    <div x-data>
        <template x-for="(item, idx) in Alpine.store('cart').items" :key="idx">
            <div class="flex justify-between items-center border-b py-2">
                <div>
                    <div x-text="item.name"></div>
                    <div class="text-sm text-gray-500">Qty: <span x-text="item.qty"></span></div>
                </div>
                <div>
                    <div>$<span x-text="(item.price * item.qty).toFixed(2)"></span></div>
                    <button @click="Alpine.store('cart').remove(idx)" class="text-red-500">Remove</button>
                </div>
            </div>
        </template>

        <div class="mt-4">
            <div>Total: $<span x-text="Alpine.store('cart').total()"></span></div>
            <a href="{{ route('checkout') }}" class="mt-2 inline-block px-4 py-2 bg-blue-600 text-white rounded">Checkout</a>
        </div>
    </div>
</div>
@endsection
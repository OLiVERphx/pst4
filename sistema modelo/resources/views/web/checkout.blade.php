@extends('layouts.web')

@section('content')
<div class="bg-white p-6 rounded shadow max-w-2xl mx-auto">
    <h2 class="text-xl font-bold">Checkout</h2>
    <form method="POST" action="{{ route('checkout.store') }}" enctype="multipart/form-data" id="checkoutForm">
        @csrf
        <input type="hidden" name="cart" id="cartInput">

        <div class="mt-4">
            <label>Recipient name</label>
            <input type="text" name="entrega_nombre" class="w-full border px-2 py-1" required>
        </div>
        <div class="mt-4">
            <label>Phone</label>
            <input type="text" name="entrega_telefono" class="w-full border px-2 py-1" required>
        </div>
        <div class="mt-4">
            <label>Address</label>
            <textarea name="entrega_direccion" class="w-full border px-2 py-1" required></textarea>
        </div>
        <div class="mt-4">
            <label>City</label>
            <input type="text" name="entrega_ciudad" class="w-full border px-2 py-1" required>
        </div>

        <div class="mt-4">
            <label>Payment method</label>
            <select name="metodo" class="w-full border px-2 py-1">
                <option value="transferencia">Transferencia</option>
                <option value="pagomovil">PagoMovil</option>
                <option value="binance">Binance</option>
                <option value="fisico">Fisico</option>
            </select>
        </div>

        <div class="mt-4">
            <label>Receipt (optional)</label>
            <input type="file" name="comprobante" accept="image/*,application/pdf">
        </div>

        <div class="mt-6">
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded">Place order</button>
        </div>
    </form>
</div>

<script>
    // copy cart from localStorage into hidden input before submit
    document.getElementById('checkoutForm').addEventListener('submit', function(e){
        var cart = localStorage.getItem('sw_cart') || '[]';
        document.getElementById('cartInput').value = cart;
    });
</script>
@endsection
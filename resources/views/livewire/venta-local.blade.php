<!-- Vista mínima para el componente VentaLocal.
     Debe usar tokens visuales del prototipo; aquí se deja una estructura simple
     que será estilizada siguiendo SmartphoneWorld_Panel.html cuando se integre. -->
<div class="panel">
    <h3>Venta en Local</h3>

    <div>
        <label>Buscar producto (nombre o código)</label>
        <input type="text" wire:model.debounce.300ms="query" placeholder="Buscar...">
        <div class="search-results">
            @foreach($results as $r)
                <div class="result-item">
                    <strong>{{ $r['nombre'] }}</strong> <small>{{ $r['codigo'] }}</small>
                    <div>Stock: {{ $r['stock'] }}</div>
                    <button wire:click.prevent="addItem({{ $r['id'] }}, 'detal', 1)">Agregar (Detall)</button>
                    <button wire:click.prevent="addItem({{ $r['id'] }}, 'mayor', 1)">Agregar (Mayor)</button>
                </div>
            @endforeach
        </div>
    </div>

    <h4>Items</h4>
    <table>
        <thead>
            <tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th><th></th></tr>
        </thead>
        <tbody>
            @foreach($items as $i => $it)
                <tr>
                    <td>{{ $it['nombre'] }} ({{ $it['codigo'] }})</td>
                    <td><input type="number" value="{{ $it['cantidad'] }}" wire:change="updateQuantity({{ $i }}, $event.target.value)"></td>
                    <td>{{ number_format($it['precio'], 2) }}</td>
                    <td>{{ number_format($it['precio'] * $it['cantidad'], 2) }}</td>
                    <td><button wire:click.prevent="removeItem({{ $i }})">Quitar</button></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h4>Cliente (opcional)</h4>
    <div>
        <label>Cédula</label>
        <input type="text" wire:model.lazy="cliente_cedula">
        <label>Teléfono</label>
        <input type="text" wire:model.lazy="cliente_telefono">
    </div>

    <div>
        <label>Notas</label>
        <textarea wire:model.lazy="notas"></textarea>
    </div>

    <div>
        <button wire:click.prevent="confirmSale">Confirmar venta (Dejar pendiente de pago)</button>
    </div>

    @if($errors->any())
        <div class="errors">
            @foreach($errors->all() as $err)
                <div>{{ $err }}</div>
            @endforeach
        </div>
    @endif

    @if(session()->has('success'))
        <div class="success">{{ session('success') }}</div>
    @endif
</div>

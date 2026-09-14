<div>
    <div class="d-flex mb-3">
        <input type="text" wire:model.live="search" class="form-control me-2" placeholder="Buscar cliente...">
        <select wire:model="statusFilter" class="form-select" style="width:160px;">
            <option value="">Todos</option>
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
        </select>
    </div>

    <table class="table table-striped">
        <thead>
            <tr><th>Avatar</th><th>Nombre</th><th>Cédula</th><th>Teléfono</th><th>Ciudad</th><th>#Pedidos</th><th>Total</th><th>Estado</th><th>Acciones</th></tr>
        </thead>
        <tbody>
            @foreach($clients as $client)
                <tr>
                    <td><div class="avatar bg-secondary text-white rounded-circle text-center" style="width:36px;height:36px;line-height:36px;">{{ strtoupper(substr($client->name,0,1)) }}</div></td>
                    <td>{{ $client->name }}</td>
                    <td>{{ $client->cedula }}</td>
                    <td>{{ $client->telefono }}</td>
                    <td>{{ $client->ciudad }}</td>
                    <td>{{ $client->orders->count() }}</td>
                    <td>${{ number_format($client->orders->sum('total'),2) }}</td>
                    <td>
                        @if($client->activo)
                            <span class="badge bg-success">Activo</span>
                        @else
                            <span class="badge bg-secondary">Inactivo</span>
                        @endif
                    </td>
                    <td>
                        <button wire:click="toggleActivo({{ $client->id }})" class="btn btn-sm btn-warning">Toggle</button>
                        <button wire:click="abrirDetalle({{ $client->id }})" class="btn btn-sm btn-primary">Detalle</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $clients->links() }}

    @if($mostrarDetalle && $selectedClient)
        <div class="modal show d-block" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Cliente: {{ $selectedClient->name }}</h5>
                        <button type="button" class="btn-close" wire:click="$set('mostrarDetalle', false)"></button>
                    </div>
                    <div class="modal-body">
                        <h6>Historial de pedidos</h6>
                        <table class="table">
                            <thead><tr><th>#</th><th>Total</th><th>Estado</th><th>Fecha</th></tr></thead>
                            <tbody>
                                @foreach($selectedClient->orders as $o)
                                    <tr>
                                        <td>{{ $o->numero_pedido }}</td>
                                        <td>${{ number_format($o->total,2) }}</td>
                                        <td>{{ $o->estado }}</td>
                                        <td>{{ $o->created_at }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" wire:click="$set('mostrarDetalle', false)">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
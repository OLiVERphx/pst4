<div>
    <h5>Alertas de stock</h5>
    <table class="table table-sm">
        <thead>
            <tr><th>Producto</th><th>Tipo</th><th>Leído</th><th>Fecha</th><th>Acciones</th></tr>
        </thead>
        <tbody>
            @foreach($alerts as $alert)
                <tr>
                    <td>{{ optional($alert->product)->nombre }}<br><small>{{ optional($alert->product)->codigo }}</small></td>
                    <td>
                        @if($alert->tipo === 'low_stock')
                            <span class="badge bg-warning">Stock bajo</span>
                        @elseif($alert->tipo === 'sin_stock')
                            <span class="badge bg-danger">Sin stock</span>
                        @else
                            <span class="badge bg-secondary">{{ $alert->tipo }}</span>
                        @endif
                    </td>
                    <td>@if($alert->leido) <span class="text-success">Sí</span> @else <strong>No</strong> @endif</td>
                    <td>{{ $alert->created_at }}</td>
                    <td>
                        <button wire:click="openReponer({{ $alert->producto_id }})" class="btn btn-sm btn-success">Reponer</button>
                        <button wire:click="markAsRead({{ $alert->id }})" class="btn btn-sm btn-secondary">Marcar como leída</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($mostrarModal)
        <div class="modal show d-block" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Reposición</h5>
                        <button type="button" class="btn-close" wire:click="$set('mostrarModal', false)"></button>
                    </div>
                    <form wire:submit.prevent="saveReponer">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label>Producto</label>
                                <select wire:model.defer="form.producto_id" class="form-select">
                                    <option value="">Seleccionar</option>
                                    @foreach(\App\Models\Product::orderBy('nombre')->get() as $p)
                                        <option value="{{ $p->id }}">{{ $p->nombre }} ({{ $p->codigo }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label>Cantidad</label>
                                <input type="number" wire:model.defer="form.cantidad" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label>Referencia</label>
                                <input type="text" wire:model.defer="form.referencia" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label>Notas</label>
                                <textarea wire:model.defer="form.notas" class="form-control"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="$set('mostrarModal', false)">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
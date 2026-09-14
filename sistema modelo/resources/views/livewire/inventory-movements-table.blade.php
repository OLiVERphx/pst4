<div>
    <div class="d-flex mb-3">
        <input type="text" wire:model.live="search" class="form-control me-2" placeholder="Buscar producto...">

        <select wire:model="typeFilter" class="form-select me-2" style="width:180px;">
            <option value="">Todos los tipos</option>
            <option value="entrada">Entrada</option>
            <option value="salida">Salida</option>
            <option value="ajuste">Ajuste</option>
            <option value="reserva">Reserva</option>
            <option value="liberacion">Liberación</option>
        </select>

        <input type="date" wire:model="dateFrom" class="form-control me-2" />
        <input type="date" wire:model="dateTo" class="form-control me-2" />

        <button wire:click="abrirCrear" class="btn btn-primary ms-auto">Nuevo movimiento</button>
    </div>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Producto</th>
                <th>Tipo</th>
                <th>Cantidad</th>
                <th>Antes</th>
                <th>Después</th>
                <th>Referencia</th>
                <th>Usuario</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movements as $m)
                <tr>
                    <td>{{ $m->created_at }}</td>
                    <td>{{ optional($m->product)->nombre }}<br><small class="text-muted">{{ optional($m->product)->codigo }}</small></td>
                    <td>
                        @if($m->tipo === 'entrada' || $m->tipo === 'liberacion')
                            <span class="badge bg-success">{{ $m->tipo }}</span>
                        @else
                            <span class="badge bg-danger">{{ $m->tipo }}</span>
                        @endif
                    </td>
                    <td>{{ $m->cantidad }}</td>
                    <td>{{ $m->cantidad_anterior }}</td>
                    <td>{{ $m->cantidad_nueva }}</td>
                    <td>{{ $m->referencia }}</td>
                    <td>{{ optional($m->user)->name }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div>
        {{ $movements->links() }}
    </div>

    <!-- Modal -->
    @if($mostrarModal)
        <div class="modal show d-block" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Nuevo movimiento</h5>
                        <button type="button" class="btn-close" wire:click="$set('mostrarModal', false)"></button>
                    </div>
                    <form wire:submit.prevent="save">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label>Producto</label>
                                <select wire:model.defer="form.producto_id" class="form-select">
                                    <option value="">Seleccionar</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}">{{ $p->nombre }} ({{ $p->codigo }})</option>
                                    @endforeach
                                </select>
                                @error('form.producto_id') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label>Tipo</label>
                                <select wire:model.defer="form.tipo" class="form-select">
                                    <option value="entrada">Entrada</option>
                                    <option value="salida">Salida</option>
                                    <option value="ajuste">Ajuste</option>
                                    <option value="reserva">Reserva</option>
                                    <option value="liberacion">Liberación</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label>Cantidad</label>
                                <input type="number" wire:model.defer="form.cantidad" class="form-control">
                                @error('form.cantidad') <div class="text-danger">{{ $message }}</div> @enderror
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
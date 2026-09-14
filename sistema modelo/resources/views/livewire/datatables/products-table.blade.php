<div>
    <!-- Barra superior: búsqueda, filtros y botón nuevo -->
    <div class="d-flex mb-3">
        <input type="text" wire:model.live="search" class="form-control me-2" placeholder="Buscar por nombre o código...">

        <select wire:model="categoryFilter" class="form-select me-2" style="width:220px;">
            <option value="">Todas las categorías</option>
            @foreach($categories as $c)
                <option value="{{ $c->id }}">{{ $c->nombre }}</option>
            @endforeach
        </select>

        <select wire:model="brandFilter" class="form-select me-2" style="width:220px;">
            <option value="">Todas las marcas</option>
            @foreach($brands as $b)
                <option value="{{ $b->id }}">{{ $b->nombre }}</option>
            @endforeach
        </select>

        <button wire:click="abrirCrear" class="btn btn-primary ms-auto">Nuevo producto</button>
    </div>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>📱</th>
                <th wire:click.prevent="ordenarPor('nombre')" style="cursor:pointer">Nombre</th>
                <th wire:click.prevent="ordenarPor('categoria_id')" style="cursor:pointer">Categoría</th>
                <th wire:click.prevent="ordenarPor('precio_detal')" style="cursor:pointer">Precio detal</th>
                <th wire:click.prevent="ordenarPor('precio_mayor')" style="cursor:pointer">Precio mayor</th>
                <th>Stock</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
                <tr>
                    <td>📱</td>
                    <td>
                        <strong>{{ $product->nombre }}</strong>
                        <br>
                        <small class="text-muted">{{ $product->codigo }}</small>
                    </td>
                    <td>{{ optional($product->category)->nombre }}</td>
                    <td>${{ number_format($product->precio_detal, 2) }}</td>
                    <td>${{ number_format($product->precio_mayor, 2) }}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <span class="me-2 {{ $product->stock <= $product->stock_minimo ? 'text-danger' : 'text-success' }}">{{ $product->stock }}</span>
                            <div style="flex:1;">
                                @php
                                    $pct = $product->stock_minimo > 0 ? min(100, ($product->stock / max(1, $product->stock_minimo)) * 100) : 100;
                                @endphp
                                <div class="progress" style="height:8px;">
                                    <div class="progress-bar" role="progressbar" style="width: {{ $pct }}%;"></div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($product->activo)
                            <span class="badge bg-success">Activo</span>
                        @else
                            <span class="badge bg-secondary">Inactivo</span>
                        @endif
                    </td>
                    <td>
                        <button wire:click="abrirEditar({{ $product->id }})" class="btn btn-sm btn-primary">Editar</button>
                        <button wire:click="toggleActivo({{ $product->id }})" class="btn btn-sm btn-warning">Toggle</button>
                        <button wire:click="delete({{ $product->id }})" onclick="return confirm('¿Eliminar producto?')" class="btn btn-sm btn-danger">Eliminar</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No hay productos.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div>
        {{ $products->links() }}
    </div>

    <!-- Modal simple para create/edit -->
    @if($mostrarModal)
        <div class="modal show d-block" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $editingProduct ? 'Editar producto' : 'Nuevo producto' }}</h5>
                        <button type="button" class="btn-close" aria-label="Close" wire:click="$set('mostrarModal', false)"></button>
                    </div>
                    <form wire:submit.prevent="save" class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Código</label>
                            <input type="text" wire:model.defer="form.codigo" class="form-control">
                            @error('form.codigo') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" wire:model.defer="form.nombre" class="form-control">
                            @error('form.nombre') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea wire:model.defer="form.descripcion" class="form-control"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Marca</label>
                                <select wire:model.defer="form.marca_id" class="form-select">
                                    <option value="">Seleccionar marca</option>
                                    @foreach($brands as $b)
                                        <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Categoría</label>
                                <select wire:model.defer="form.categoria_id" class="form-select">
                                    <option value="">Seleccionar categoría</option>
                                    @foreach($categories as $c)
                                        <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Precio detal</label>
                                <input type="number" step="0.01" wire:model.defer="form.precio_detal" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Precio mayor</label>
                                <input type="number" step="0.01" wire:model.defer="form.precio_mayor" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Precio costo</label>
                                <input type="number" step="0.01" wire:model.defer="form.precio_costo" class="form-control">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Stock</label>
                                <input type="number" wire:model.defer="form.stock" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Stock mínimo</label>
                                <input type="number" wire:model.defer="form.stock_minimo" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Min cantidad mayor</label>
                                <input type="number" wire:model.defer="form.min_cantidad_mayor" class="form-control">
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" wire:model.defer="form.activo" id="activoCheck">
                            <label class="form-check-label" for="activoCheck">Activo</label>
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
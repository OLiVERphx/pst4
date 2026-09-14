<div>
    <div class="p-3 border-b border-[#2A3047] flex items-center gap-2">
        <div class="flex items-center gap-2">
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[#94A3B8]">🔍</span>
                <input wire:model.live="search" type="text" placeholder="Buscar por nombre o código" class="bg-[#1C2130] border border-[#2A3047] rounded-lg px-3 py-1.5 text-sm w-56 pl-10 text-[#F1F5F9] placeholder-[#94A3B8] focus:border-[#1D4ED8]" />
            </div>

            <select wire:model="categoryFilter" class="bg-[#1C2130] border border-[#2A3047] rounded-lg px-3 py-1.5 text-sm text-[#F1F5F9]">
                <option value="">Todas las categorías</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                @endforeach
            </select>

            <select wire:model="brandFilter" class="bg-[#1C2130] border border-[#2A3047] rounded-lg px-3 py-1.5 text-sm text-[#F1F5F9]">
                <option value="">Todas las marcas</option>
                @foreach($brands as $b)
                    <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div class="ml-auto">
            <button wire:click="openCreate" class="bg-gradient-to-r from-[#1D4ED8] to-[#0D9488] text-white px-3 py-2 rounded-lg">Nuevo producto</button>
        </div>
    </div>

    <div class="bg-[#131720] border border-[#2A3047] rounded-xl overflow-hidden">
        <table class="min-w-full divide-y divide-[#2A3047]">
            <thead class="bg-[#1C2130]"><tr>
                    <th class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-3 text-left">Cat</th>
                    <th class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-3 text-left cursor-pointer" wire:click="sortBy('nombre')">Nombre / Código</th>
                    <th class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-3 text-left cursor-pointer" wire:click="sortBy('precio_detal')">Precio detal</th>
                    <th class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-3 text-left cursor-pointer" wire:click="sortBy('precio_mayor')">Precio mayor</th>
                    <th class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-3 text-left">Stock</th>
                    <th class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-3 text-left">Nivel</th>
                    <th class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-3 text-left">Estado</th>
                    <th class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-3 text-right">Acciones</th>
                </tr></thead>
            <tbody class="bg-[#131720] divide-y divide-[#2A3047]">
                @foreach($products as $product)
                    @php
                        $stockBadge = $product->stock <= $product->stock_minimo ? 'bg-red-500/10 text-red-400' : 'bg-[#059669]/10 text-[#059669]';
                        $activoBadge = $product->activo ? 'bg-[#059669]/10 text-[#059669]' : 'bg-red-500/10 text-red-400';
                    @endphp
                    <tr class="hover:bg-[#1C2130]">
                        <td class="px-4 py-2">📱</td>
                        <td class="px-4 py-2">
                            <div class="font-semibold text-[#F1F5F9]">{{ $product->nombre }}</div>
                            <div class="text-xs text-[#94A3B8]">{{ $product->codigo }}</div>
                        </td>
                        <td class="px-4 py-2 text-[#F1F5F9]">{{ number_format($product->precio_detal, 2) }}</td>
                        <td class="px-4 py-2 text-[#F1F5F9]">{{ number_format($product->precio_mayor, 2) }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 rounded-full text-sm {{ $stockBadge }}">{{ $product->stock }}</span>
                        </td>
                        <td class="px-4 py-2">
                            <div class="w-40 bg-[#1C2130] rounded h-2">
                                <div class="bg-[#1D4ED8] h-2 rounded" style="width: {{ min(100, ($product->stock / max(1, $product->stock_minimo)) * 100) }}%"></div>
                            </div>
                        </td>
                        <td class="px-4 py-2">
                            <span class="{{ $activoBadge }} px-2 py-1 rounded-full text-xs font-semibold">{{ $product->activo ? 'Activo' : 'Inactivo' }}</span>
                        </td>
                        <td class="px-4 py-2 text-right">
                            <button wire:click="openEdit({{ $product->id }})" class="text-[#1D4ED8] mr-2">Editar</button>
                            <button wire:click="toggleActive({{ $product->id }})" class="text-[#EA580C] mr-2">Toggle</button>
                            <button wire:click="delete({{ $product->id }})" class="text-red-500">Eliminar</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="p-4">{{ $products->links() }}</div>
    </div>

    <!-- Modal simple -->
    <div x-data="{}" wire:ignore.self>
        <div x-show="$wire.showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
            <div class="bg-[#2B3346] rounded shadow-xl w-2/3 p-4 border border-[#384457]">
                <h3 class="text-lg font-semibold mb-2 text-[#F1F5F9]">{{ $editingProduct ? 'Editar producto' : 'Nuevo producto' }}</h3>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm text-[#94A3B8]">Código</label>
                        <input wire:model="form.codigo" class="border rounded px-2 py-1 w-full bg-[#1C2130] border-[#384457] text-[#F1F5F9]" />
                    </div>
                    <div>
                        <label class="block text-sm text-[#94A3B8]">Nombre</label>
                        <input wire:model="form.nombre" class="border rounded px-2 py-1 w-full bg-[#1C2130] border-[#384457] text-[#F1F5F9]" />
                    </div>
                    <div>
                        <label class="block text-sm text-[#94A3B8]">Marca</label>
                        <select wire:model="form.marca_id" class="border rounded px-2 py-1 w-full bg-[#1C2130] border-[#384457] text-[#F1F5F9]">
                            <option value="">--</option>
                            @foreach($brands as $b)
                                <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-[#94A3B8]">Categoría</label>
                        <select wire:model="form.categoria_id" class="border rounded px-2 py-1 w-full bg-[#1C2130] border-[#384457] text-[#F1F5F9]">
                            <option value="">--</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm text-[#94A3B8]">Precio detal</label>
                        <input wire:model="form.precio_detal" type="number" step="0.01" class="border rounded px-2 py-1 w-full bg-[#1C2130] border-[#384457] text-[#F1F5F9]" />
                    </div>
                    <div>
                        <label class="block text-sm text-[#94A3B8]">Precio mayor</label>
                        <input wire:model="form.precio_mayor" type="number" step="0.01" class="border rounded px-2 py-1 w-full bg-[#1C2130] border-[#384457] text-[#F1F5F9]" />
                    </div>

                    <div>
                        <label class="block text-sm text-[#94A3B8]">Stock</label>
                        <input wire:model="form.stock" type="number" class="border rounded px-2 py-1 w-full bg-[#1C2130] border-[#384457] text-[#F1F5F9]" />
                    </div>
                    <div>
                        <label class="block text-sm text-[#94A3B8]">Stock mínimo</label>
                        <input wire:model="form.stock_minimo" type="number" class="border rounded px-2 py-1 w-full bg-[#1C2130] border-[#384457] text-[#F1F5F9]" />
                    </div>
                </div>

                <div class="mt-4 text-right">
                    <button wire:click.prevent="save" class="bg-[#059669] text-white px-3 py-2 rounded">Guardar</button>
                    <button wire:click.prevent="$set('showModal', false)" class="bg-[#2A3047] text-white px-3 py-2 rounded">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
</div>
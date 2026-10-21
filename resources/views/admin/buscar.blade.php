@extends('layouts.admin')

@section('pageTitle', 'Buscar')

@php
    $usd = fn ($n) => '$' . number_format((float) $n, 2, ',', '.');
    $titulos = [
        'pedidos' => ['📦 Pedidos', 'admin.pedidos.lista'],
        'productos' => ['🗂️ Productos', 'admin.productos.lista'],
        'clientes' => ['👥 Clientes', 'admin.clients.lista'],
    ];
    $card = 'bg-[#334155] border border-[#475569] rounded-xl p-5';
@endphp

@section('content')
<div class="container mx-auto py-6 space-y-4">
    {{-- En el celular la barra superior no tiene espacio para el buscador --}}
    <form method="GET" action="{{ route('admin.buscar') }}" role="search" class="sm:hidden">
        <input type="search" name="q" value="{{ $q }}" minlength="2" maxlength="100" autofocus
               placeholder="Pedido, producto o cliente..." aria-label="Buscar en el panel"
               class="w-full px-4 py-2.5 bg-[#475569] border border-[#475569] rounded-lg text-sm text-[#F1F5F9] focus:border-[#1D4ED8]" />
    </form>
    @if (mb_strlen($q) < 2)
        <div class="{{ $card }} text-sm text-[#A0AEC0]">Escribe al menos 2 caracteres: número de pedido, nombre o código de producto, nombre, correo o cédula de cliente.</div>
    @elseif (! $secciones)
        <div class="{{ $card }} text-sm text-[#A0AEC0]">Tu rol no tiene acceso a pedidos, productos ni clientes.</div>
    @else
        <p class="text-sm text-[#A0AEC0]">Resultados para «<span class="text-[#F1F5F9]">{{ $q }}</span>»</p>

        @foreach ($secciones as $tipo => $resultados)
            <div class="{{ $card }}">
                <div class="mb-3 flex items-baseline justify-between">
                    <h3 class="font-bold text-[#F1F5F9]">{{ $titulos[$tipo][0] }}</h3>
                    @if ($resultados->isNotEmpty())
                        <a href="{{ route($titulos[$tipo][1], ['q' => $q]) }}" class="text-xs font-semibold text-[#60A5FA] hover:underline">Ver todos en la lista →</a>
                    @endif
                </div>

                @if ($resultados->isEmpty())
                    <p class="text-sm text-[#A0AEC0]">Sin coincidencias.</p>
                @else
                    <ul class="divide-y divide-[#475569]/60 text-sm">
                        @foreach ($resultados as $r)
                            <li>
                                @switch($tipo)
                                    @case('pedidos')
                                        <a href="{{ route('admin.pedidos.lista', ['q' => $r->numero_pedido]) }}" class="flex flex-wrap items-center justify-between gap-2 py-2 hover:text-[#60A5FA]">
                                            <span class="font-semibold text-[#F1F5F9]">{{ $r->numero_pedido }}</span>
                                            <span class="text-[#CBD5E1]">{{ trim(($r->user->name ?? 'Venta en tienda') . ' ' . ($r->user->apellido ?? '')) }}</span>
                                            <span class="text-[#A0AEC0]">{{ ucfirst(str_replace('_', ' ', $r->estado)) }} · {{ $usd($r->total) }} · {{ $r->created_at->format('d/m/Y') }}</span>
                                        </a>
                                        @break
                                    @case('productos')
                                        <a href="{{ route('admin.productos.lista', ['q' => $r->codigo ?: $r->nombre]) }}" class="flex flex-wrap items-center justify-between gap-2 py-2 hover:text-[#60A5FA]">
                                            <span class="font-semibold text-[#F1F5F9]">{{ $r->nombre }} @unless ($r->activo)<span class="text-xs font-normal text-[#A0AEC0]">(inactivo)</span>@endunless</span>
                                            <span class="text-[#A0AEC0]">{{ $r->codigo }} · {{ $usd($r->precio_detal) }} ·
                                                <span class="{{ $r->stock <= $r->stock_minimo ? 'text-[#FB923C]' : '' }}">stock {{ $r->stock }}</span></span>
                                        </a>
                                        @break
                                    @case('clientes')
                                        <a href="{{ route('admin.clients.lista', ['q' => $r->email]) }}" class="flex flex-wrap items-center justify-between gap-2 py-2 hover:text-[#60A5FA]">
                                            <span class="font-semibold text-[#F1F5F9]">{{ trim($r->name . ' ' . $r->apellido) }} @unless ($r->activo)<span class="text-xs font-normal text-[#A0AEC0]">(inactivo)</span>@endunless</span>
                                            <span class="text-[#A0AEC0]">{{ $r->email }}</span>
                                        </a>
                                        @break
                                @endswitch
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    @endif
</div>
@endsection

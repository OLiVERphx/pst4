@extends('layouts.admin')

@section('pageTitle', 'Configuración')

@section('content')
<div class="container mx-auto py-6">
    <h1 class="text-2xl font-semibold mb-4 text-[#F1F5F9]">Configuración</h1>

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-500/40 bg-red-500/10 p-3 text-sm text-red-200">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Datos de la empresa -->
        <div class="bg-[#131720] border border-[#2A3047] rounded-xl p-5">
            <h3 class="font-bold text-[#F1F5F9] mb-2">Datos de la empresa</h3>
            <div class="space-y-3">
                <input class="w-full bg-[#1C2130] border border-[#384457] rounded-lg px-3 py-2 text-sm text-[#F1F5F9]" value="Smartphone World S.A." readonly />
                <input class="w-full bg-[#1C2130] border border-[#384457] rounded-lg px-3 py-2 text-sm text-[#F1F5F9]" value="J-12345678-9" readonly />
                <input class="w-full bg-[#1C2130] border border-[#384457] rounded-lg px-3 py-2 text-sm text-[#F1F5F9]" value="+58 212-555-0123" readonly />
            </div>
        </div>

        <!-- Mi perfil -->
        <div class="bg-[#131720] border border-[#2A3047] rounded-xl p-5">
            <h3 class="font-bold text-[#F1F5F9] mb-2">Mi perfil</h3>
            <div class="space-y-3">
                <div class="text-sm text-[#94A3B8]">Nombre</div>
                <div class="text-[#F1F5F9] font-semibold">{{ auth()->user()->name }}</div>
                <div class="text-sm text-[#94A3B8]">Email</div>
                <div class="text-[#F1F5F9]">{{ auth()->user()->email }}</div>
                <div class="text-sm text-[#94A3B8]">Nueva contraseña</div>
                <input type="password" class="w-full bg-[#1C2130] border border-[#2A3047] rounded-lg px-3 py-2 text-sm text-[#F1F5F9]" />
            </div>
        </div>

        <!-- Notificaciones -->
        <div class="bg-[#131720] border border-[#2A3047] rounded-xl p-5">
            <h3 class="font-bold text-[#F1F5F9] mb-2">Notificaciones</h3>
            <ul class="space-y-3 text-sm">
                <li class="flex items-center justify-between">
                    <div class="text-[#F1F5F9]">Alerta: stock bajo</div>
                    <span class="ml-2 bg-[#059669]/10 text-[#34D399] text-[11px] font-bold rounded-full px-2 py-0.5">Activo</span>
                </li>
                <li class="flex items-center justify-between">
                    <div class="text-[#F1F5F9]">Nuevo pedido</div>
                    <span class="ml-2 bg-[#059669]/10 text-[#34D399] text-[11px] font-bold rounded-full px-2 py-0.5">Activo</span>
                </li>
                <li class="flex items-center justify-between">
                    <div class="text-[#F1F5F9]">Pago recibido</div>
                    <span class="ml-2 bg-[#059669]/10 text-[#34D399] text-[11px] font-bold rounded-full px-2 py-0.5">Activo</span>
                </li>
                <li class="flex items-center justify-between">
                    <div class="text-[#F1F5F9]">Reporte semanal</div>
                    <span class="ml-2 bg-[#059669]/10 text-[#34D399] text-[11px] font-bold rounded-full px-2 py-0.5">Activo</span>
                </li>
            </ul>
        </div>

        <!-- Sistema -->
        <div class="bg-[#131720] border border-[#2A3047] rounded-xl p-5">
            <h3 class="font-bold text-[#F1F5F9] mb-2">Sistema</h3>
            <div class="text-sm space-y-2 text-[#F1F5F9]">
                <div>Versión: 1.0.0</div>
                <div>Laravel: {{ app()->version() }}</div>
                <div>BD: MySQL 8.0</div>
                <div>Total productos: {{ $productCount ?? 0 }}</div>
                <div>Total pedidos: {{ $orderCount ?? 0 }}</div>
                <div>Total clientes: {{ $totalClients ?? 0 }}</div>
            </div>

            <div class="mt-4 text-right">
                <button class="px-4 py-2 rounded-lg bg-[#1C2130] border border-[#2A3047] text-[#94A3B8]">Limpiar caché</button>
            </div>
        </div>
    </div>

    <div class="mt-6 bg-[#131720] border border-[#2A3047] rounded-xl p-5">
        <h3 class="font-bold text-[#F1F5F9] mb-4">Configuración general</h3>

        <form method="POST" action="{{ route('admin.configuracion.update') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-[#94A3B8] mb-1">Tasa BCV</label>
                    <input type="number" step="any" name="tasa_bcv" value="{{ old('tasa_bcv', $config->tasa_bcv ?? '') }}" class="w-full bg-[#1C2130] border border-[#384457] rounded-lg px-3 py-2 text-sm text-[#F1F5F9]" />
                </div>

                <div>
                    <label class="block text-sm text-[#94A3B8] mb-1">Tasa Binance</label>
                    <input type="number" step="any" name="tasa_binance" value="{{ old('tasa_binance', $config->tasa_binance ?? '') }}" class="w-full bg-[#1C2130] border border-[#384457] rounded-lg px-3 py-2 text-sm text-[#F1F5F9]" />
                </div>

                <div>
                    <label class="block text-sm text-[#94A3B8] mb-1">Mínimo compra mayorista (USD)</label>
                    <input type="number" step="any" name="minimo_compra_mayor_usd" value="{{ old('minimo_compra_mayor_usd', $config->minimo_compra_mayor_usd ?? '') }}" class="w-full bg-[#1C2130] border border-[#384457] rounded-lg px-3 py-2 text-sm text-[#F1F5F9]" />
                </div>

                <div>
                    <label class="block text-sm text-[#94A3B8] mb-1">Mínimo unidades mayorista</label>
                    <input type="number" step="1" name="minimo_unidades_mayor" value="{{ old('minimo_unidades_mayor', $config->minimo_unidades_mayor ?? '') }}" class="w-full bg-[#1C2130] border border-[#384457] rounded-lg px-3 py-2 text-sm text-[#F1F5F9]" />
                </div>
            </div>

            <div class="mt-4 text-right">
                <button type="submit" class="px-4 py-2 rounded-lg bg-[#1C2130] border border-[#2A3047] text-[#F1F5F9]">Guardar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('pageTitle', 'Códigos de recuperación')

@section('content')
<div class="container mx-auto py-6 max-w-2xl">
    <div class="bg-[#334155] border border-[#475569] rounded-xl p-6">
        <h2 class="text-lg font-bold text-[#F1F5F9]">Guarda tus códigos de recuperación</h2>
        <p class="text-sm text-[#A0AEC0] mt-1">Si pierdes o cambias el teléfono, entra con uno de estos códigos. <strong class="text-amber-300">Se muestran solo esta vez</strong> y cada uno sirve una sola vez.</p>

        <ul id="codigos" class="mt-5 grid grid-cols-2 gap-2 font-mono text-[#F1F5F9]">
            @foreach ($codigos as $codigo)
                <li class="rounded-lg bg-[#1E293B] border border-[#475569] px-3 py-2 text-center tracking-wider">{{ $codigo }}</li>
            @endforeach
        </ul>

        <div class="mt-5 flex flex-wrap gap-2">
            <button type="button" onclick="navigator.clipboard.writeText(@js(implode("\n", $codigos))).then(() => this.textContent = '✓ Copiados')"
                    class="rounded-lg border border-[#475569] px-4 py-2 text-sm text-[#F1F5F9] hover:bg-[#475569]">Copiar</button>
            <a download="smartphone-world-codigos-recuperacion.txt"
               href="data:text/plain;charset=utf-8,{{ rawurlencode("Smartphone World - códigos de recuperación\n" . auth()->user()->email . "\n\n" . implode("\n", $codigos) . "\n") }}"
               class="rounded-lg border border-[#475569] px-4 py-2 text-sm text-[#F1F5F9] hover:bg-[#475569]">Descargar .txt</a>
            <button type="button" onclick="window.print()" class="rounded-lg border border-[#475569] px-4 py-2 text-sm text-[#F1F5F9] hover:bg-[#475569]">Imprimir</button>
        </div>

        <p class="mt-5 text-xs text-[#A0AEC0]">Guárdalos fuera del teléfono: impresos, en un gestor de contraseñas o con los respaldos del negocio.</p>

        <a href="{{ route('admin.dashboard') }}" class="mt-6 inline-block rounded-lg bg-blue-600 hover:bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white">Ya los guardé, ir al panel</a>
    </div>
</div>
@endsection

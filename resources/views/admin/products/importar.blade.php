@extends('layouts.admin')

@section('pageTitle', 'Importar productos')

@section('content')
<div class="container mx-auto py-6">
    <div class="mb-5 flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-[#F1F5F9]">Importar productos desde Excel</h2>
            <p class="text-xs text-[#A0AEC0]">Crea o actualiza productos a partir de una hoja de cálculo. Nada se guarda hasta confirmar la vista previa.</p>
        </div>
        <a href="{{ route('admin.productos.lista') }}" class="text-sm text-[#A0AEC0] hover:text-[#F1F5F9]">← Volver a productos</a>
    </div>

    <livewire:importar-productos />
</div>
@endsection

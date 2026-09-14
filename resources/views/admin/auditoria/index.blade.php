@extends('layouts.admin')

@section('pageTitle', 'Bitácora de Auditoría')

@section('content')
<div class="container mx-auto">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#F1F5F9] flex items-center gap-2">
                <span>🛡️</span>
                <span>Bitácora de Auditoría</span>
            </h1>
            <p class="text-xs text-[#94A3B8] mt-1">
                Registro inmutable de trazabilidad y operaciones críticas realizadas en el sistema.
            </p>
        </div>
    </div>

    <!-- Componente Livewire de la Tabla de Auditoría -->
    <livewire:datatables.bitacora-auditoria-table />
</div>
@endsection

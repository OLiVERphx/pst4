@extends('layouts.admin')
@section('pageTitle', 'Respaldos de Base de Datos')
@section('content')
<div class="container mx-auto py-6">
    <div class="mb-5 flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-[#F1F5F9]">Respaldos de Seguridad</h2>
            <p class="text-xs text-[#A0AEC0]">Generación y descarga de volcados SQL de la base de datos centralizada (Solo Superadmin).</p>
        </div>
    </div>

    <livewire:datatables.respaldos-table />
</div>
@endsection

@extends('layouts.admin')
@section('pageTitle', 'Gestión de Usuarios')
@section('content')
<div class="container mx-auto py-6">
    <div class="mb-5 flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-[#F1F5F9]">Control de Usuarios y Roles</h2>
            <p class="text-xs text-[#A0AEC0]">Administración centralizada de cuentas de acceso, roles y permisos (Solo Superadmin).</p>
        </div>
    </div>

    <livewire:datatables.usuarios-table />
</div>
@endsection

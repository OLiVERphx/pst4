@extends('layouts.admin')

@section('pageTitle', 'Mi cuenta')

@section('content')
<div class="container mx-auto py-6">
    @if (session('success'))
        <div class="mb-4 rounded-lg border border-emerald-500/40 bg-emerald-500/10 p-3 text-sm text-emerald-200">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        @include('admin.perfil._password')
        @include('admin.perfil._notificaciones')
        @include('admin.perfil._dos_factores')
    </div>
</div>
@endsection

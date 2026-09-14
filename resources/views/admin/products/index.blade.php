@extends('layouts.admin')

@section('pageTitle', 'Productos')

@section('content')
    <div class="container mx-auto py-6">
        <h1 class="text-2xl font-semibold mb-4">Productos</h1>
        <livewire:datatables.products-table />
    </div>
@endsection

@extends('layouts.admin')
@section('pageTitle', 'Inventario')
@section('content')
<div class="container mx-auto py-6">
    <livewire:datatables.inventory-movements-table />
</div>
@endsection

@extends('layouts.admin')
@section('pageTitle', 'Pedidos')
@section('content')
<div class="container mx-auto py-6">
    <livewire:datatables.orders-table />
</div>
@endsection

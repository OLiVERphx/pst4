@extends('layouts.admin')
@section('pageTitle', 'Clientes')
@section('content')
<div class="container mx-auto py-6">
    <livewire:datatables.clients-table />
</div>
@endsection

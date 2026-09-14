@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Productos</h1>
</div>

<livewire:datatables.products-table />

@endsection

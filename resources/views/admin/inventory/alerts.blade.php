@extends('layouts.admin')
@section('pageTitle', 'Alertas de Stock')
@section('content')
<div class="container mx-auto py-6">
    <livewire:datatables.stock-alerts />
</div>
@endsection

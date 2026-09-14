@extends('vendor.adminlte.master')

@section('body')
<div class="wrapper">
    {{-- Barra superior y sidebar provistos por AdminLTE --}}

    <div class="content-wrapper">
        <section class="content">
            <div class="container-fluid">
                @yield('content')
            </div>
        </section>
    </div>
</div>
@endsection

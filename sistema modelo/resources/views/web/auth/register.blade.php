@extends('layouts.web')

@section('content')
<div class="max-w-md mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-xl font-bold">Register</h2>
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="mt-4">
            <label>Name</label>
            <input type="text" name="name" class="w-full border px-2 py-1">
        </div>
        <div class="mt-4">
            <label>Email</label>
            <input type="email" name="email" class="w-full border px-2 py-1">
        </div>
        <div class="mt-4">
            <label>Password</label>
            <input type="password" name="password" class="w-full border px-2 py-1">
        </div>
        <div class="mt-4">
            <button class="px-4 py-2 bg-green-600 text-white rounded">Register</button>
        </div>
    </form>
</div>
@endsection
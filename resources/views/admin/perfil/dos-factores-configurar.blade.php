@extends('layouts.admin')

@section('pageTitle', 'Verificación en dos pasos')

@section('content')
<div class="container mx-auto py-6 max-w-3xl">
    @if (session('success'))
        <div class="mb-4 rounded-lg border border-emerald-500/40 bg-emerald-500/10 p-3 text-sm text-emerald-200">{{ session('success') }}</div>
    @endif

    @if ($obligatorio)
        <div class="mb-4 rounded-lg border border-amber-500/40 bg-amber-500/10 p-3 text-sm text-amber-200">
            Tu rol tiene acceso a pagos, usuarios y respaldos: para usar el panel debes activar la verificación en dos pasos.
        </div>
    @endif

    <div class="bg-[#334155] border border-[#475569] rounded-xl p-6">
        <h2 class="text-lg font-bold text-[#F1F5F9]">Activar verificación en dos pasos</h2>
        <p class="text-sm text-[#A0AEC0] mt-1">Además de tu contraseña, al entrar se te pedirá un código de 6 dígitos que genera tu teléfono. Si alguien obtiene tu contraseña, no podrá entrar sin el teléfono.</p>

        <div class="mt-6 grid gap-6 md:grid-cols-[auto_1fr] items-start">
            <div class="bg-white rounded-xl p-3 w-[224px] mx-auto" aria-label="Código QR para la app de autenticación">{!! $qr !!}</div>

            <ol class="list-none space-y-4 text-sm text-[#F1F5F9]">
                <li>
                    <span class="font-semibold">1. Instala una app de autenticación</span>
                    <span class="block text-[#A0AEC0]">Google Authenticator, Microsoft Authenticator o Authy (gratis en Play Store y App Store).</span>
                </li>
                <li>
                    <span class="font-semibold">2. Escanea el código QR</span>
                    <span class="block text-[#A0AEC0]">En la app: <em>Agregar cuenta → Escanear código QR</em>. Si no puedes escanearlo, elige <em>Ingresar clave</em> y escribe:</span>
                    <code class="mt-2 inline-block select-all rounded-lg bg-[#1E293B] border border-[#475569] px-3 py-2 font-mono text-sm tracking-wider text-[#F1F5F9] break-all">{{ $clave }}</code>
                </li>
                <li>
                    <span class="font-semibold">3. Escribe el código que muestra la app</span>
                    <form method="POST" action="{{ route('admin.2fa.activar') }}" class="mt-2 flex flex-wrap items-start gap-2">
                        @csrf
                        <div>
                            <input name="codigo" inputmode="numeric" pattern="[0-9 ]*" maxlength="7" autocomplete="one-time-code" required autofocus placeholder="000000"
                                   class="w-40 bg-[#475569] border border-[#384457] rounded-lg px-3 py-2 text-center text-lg tracking-[.3em] text-[#F1F5F9]">
                            @error('codigo')<p class="text-xs text-red-300 mt-1 max-w-xs">{{ $message }}</p>@enderror
                        </div>
                        <button class="rounded-lg bg-blue-600 hover:bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white">Activar</button>
                    </form>
                </li>
            </ol>
        </div>

        <p class="mt-6 text-xs text-[#A0AEC0]">No compartas este QR ni la clave: quien los tenga puede generar tus códigos. Al recargar la página se mantiene el mismo QR hasta que lo actives.</p>
    </div>
</div>
@endsection

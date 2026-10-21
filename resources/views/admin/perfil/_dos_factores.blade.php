{{-- Estado de la verificación en dos pasos del usuario. Se usa en Mi cuenta. --}}
@php
    $usuario = auth()->user();
    $campo = 'w-full bg-[#475569] border border-[#384457] rounded-lg px-3 py-2 text-sm text-[#F1F5F9]';
    $erroresDosFactores = $errors->getBag('dosFactores');
@endphp
<div class="bg-[#334155] border border-[#475569] rounded-xl p-5 md:col-span-2">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h3 class="font-bold text-[#F1F5F9]">Verificación en dos pasos</h3>
        @if ($usuario->tieneDosFactores())
            <span class="rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-semibold text-emerald-300">Activa desde {{ $usuario->two_factor_confirmed_at->format('d/m/Y') }}</span>
        @else
            <span class="rounded-full bg-amber-500/15 px-3 py-1 text-xs font-semibold text-amber-300">Inactiva</span>
        @endif
    </div>

    @if ($usuario->tieneDosFactores())
        @php $restantes = app(\App\Services\ServicioDosFactores::class)->codigosRestantes($usuario); @endphp
        <p class="text-sm text-[#A0AEC0] mt-2">
            Al entrar se te pide el código de tu app de autenticación.
            Te quedan <strong class="{{ $restantes <= 2 ? 'text-amber-300' : 'text-[#F1F5F9]' }}">{{ $restantes }}</strong> códigos de recuperación.
        </p>

        @if ($erroresDosFactores->any())
            <p class="mt-3 rounded-lg border border-red-500/40 bg-red-500/10 p-3 text-sm text-red-200">{{ $erroresDosFactores->first() }}</p>
        @endif

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <form method="POST" action="{{ route('admin.2fa.regenerar') }}" class="space-y-2">
                @csrf
                <h4 class="text-sm font-semibold text-[#F1F5F9]">Nuevos códigos de recuperación</h4>
                <p class="text-xs text-[#A0AEC0]">Los anteriores dejan de servir.</p>
                <input type="password" name="password_actual" autocomplete="current-password" required placeholder="Tu contraseña" class="{{ $campo }}">
                <button class="rounded-lg border border-[#475569] px-4 py-2 text-sm text-[#F1F5F9] hover:bg-[#475569]">Generar códigos nuevos</button>
            </form>

            <form method="POST" action="{{ route('admin.2fa.desactivar') }}" class="space-y-2"
                  onsubmit="return confirm(@js($usuario->requiereDosFactores() ? '¿Configurar con otro teléfono? El actual dejará de servir.' : '¿Desactivar la verificación en dos pasos?'))">
                @csrf
                @method('DELETE')
                @if ($usuario->requiereDosFactores())
                    <h4 class="text-sm font-semibold text-[#F1F5F9]">Cambiar de teléfono</h4>
                    <p class="text-xs text-[#A0AEC0]">Tu rol la exige: se configura de nuevo con otro QR.</p>
                @else
                    <h4 class="text-sm font-semibold text-[#F1F5F9]">Desactivar</h4>
                    <p class="text-xs text-[#A0AEC0]">También sirve para cambiar de teléfono.</p>
                @endif
                <input type="password" name="password_actual" autocomplete="current-password" required placeholder="Tu contraseña" class="{{ $campo }}">
                <button class="rounded-lg border border-red-500/40 px-4 py-2 text-sm text-red-300 hover:bg-red-500/10">
                    {{ $usuario->requiereDosFactores() ? 'Configurar otro teléfono' : 'Desactivar' }}
                </button>
            </form>
        </div>
    @else
        <p class="text-sm text-[#A0AEC0] mt-2">Protege tu cuenta con un código de 6 dígitos de tu teléfono además de la contraseña.</p>
        <a href="{{ route('admin.2fa.configurar') }}" class="mt-3 inline-block rounded-lg bg-blue-600 hover:bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Activar</a>
    @endif
</div>

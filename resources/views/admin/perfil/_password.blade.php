{{-- Datos de la cuenta y cambio de contraseña. Se usa en Mi cuenta y en Configuración. --}}
@php
    $usuario = auth()->user();
    $campo = 'w-full bg-[#475569] border border-[#384457] rounded-lg px-3 py-2 text-sm text-[#F1F5F9]';
    $erroresPassword = $errors->getBag('password');
@endphp
<div class="bg-[#334155] border border-[#475569] rounded-xl p-5">
    <h3 class="font-bold text-[#F1F5F9] mb-3">Mi perfil</h3>
    <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm mb-4">
        <dt class="text-[#A0AEC0]">Nombre</dt>
        <dd class="text-[#F1F5F9] font-semibold">{{ trim($usuario->name . ' ' . $usuario->apellido) }}</dd>
        <dt class="text-[#A0AEC0]">Correo</dt>
        <dd class="text-[#F1F5F9] break-all">{{ $usuario->email }}</dd>
        <dt class="text-[#A0AEC0]">Rol</dt>
        <dd class="text-[#F1F5F9]">{{ ucfirst($usuario->getRoleNames()->first() ?? $usuario->role?->nombre ?? '—') }}</dd>
    </dl>

    <form method="POST" action="{{ route('admin.perfil.password') }}" class="space-y-3">
        @csrf
        @method('PUT')
        <h4 class="text-sm font-semibold text-[#F1F5F9]">Cambiar contraseña</h4>
        @if ($erroresPassword->any())
            <ul class="rounded-lg border border-red-500/40 bg-red-500/10 p-3 text-sm text-red-200">
                @foreach ($erroresPassword->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
        <div>
            <label for="password_actual" class="block text-sm text-[#A0AEC0] mb-1">Contraseña actual</label>
            <input id="password_actual" type="password" name="password_actual" autocomplete="current-password" required class="{{ $campo }}" />
        </div>
        <div>
            <label for="password_nueva" class="block text-sm text-[#A0AEC0] mb-1">Nueva contraseña</label>
            <input id="password_nueva" type="password" name="password" autocomplete="new-password" required minlength="8" class="{{ $campo }}" />
            <p class="text-xs text-[#A0AEC0] mt-1">Mínimo 8 caracteres, con letras y números.</p>
        </div>
        <div>
            <label for="password_confirmacion" class="block text-sm text-[#A0AEC0] mb-1">Repite la nueva contraseña</label>
            <input id="password_confirmacion" type="password" name="password_confirmation" autocomplete="new-password" required class="{{ $campo }}" />
        </div>
        <div class="text-right">
            <button type="submit" class="px-4 py-2 rounded-lg bg-[#1D4ED8] text-white text-sm font-semibold hover:bg-[#1E40AF]">Cambiar contraseña</button>
        </div>
    </form>
</div>

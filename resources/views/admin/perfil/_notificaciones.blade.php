{{-- Avisos que recibe el usuario. Se usa en Mi cuenta y en Configuración. --}}
@php $usuario = auth()->user(); @endphp
<form method="POST" action="{{ route('admin.perfil.notificaciones') }}" class="bg-[#334155] border border-[#475569] rounded-xl p-5">
    @csrf
    @method('PUT')
    <h3 class="font-bold text-[#F1F5F9] mb-1">Notificaciones</h3>
    <p class="text-xs text-[#A0AEC0] mb-3">Solo para tu usuario. Las alertas aparecen en la campana 🔔 de la barra superior.</p>
    <ul class="space-y-3 text-sm">
        @forelse ($usuario->notificacionesDisponibles() as $tipo => [$etiqueta, $permiso, $descripcion])
            <li>
                <label class="flex items-start justify-between gap-3 cursor-pointer">
                    <span>
                        <span class="block text-[#F1F5F9]">{{ $etiqueta }}</span>
                        <span class="block text-xs text-[#A0AEC0]">{{ $descripcion }}</span>
                    </span>
                    <input type="checkbox" name="notificaciones[]" value="{{ $tipo }}" @checked($usuario->quiereNotificacion($tipo))
                           class="mt-1 h-4 w-4 shrink-0 accent-[#059669]" />
                </label>
            </li>
        @empty
            <li class="text-[#A0AEC0]">Tu rol no tiene avisos disponibles.</li>
        @endforelse
    </ul>
    @if ($usuario->notificacionesDisponibles())
        <div class="mt-4 text-right">
            <button type="submit" class="px-4 py-2 rounded-lg bg-[#475569] border border-[#475569] text-sm text-[#F1F5F9] hover:bg-[#3B4A61]">Guardar notificaciones</button>
        </div>
    @endif
</form>

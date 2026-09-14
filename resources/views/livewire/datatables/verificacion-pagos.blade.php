<div class="relative">
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    @forelse($payments as $payment)
        @php
            $meta = $payment->metadata_comprobante ?? [];
            $checks = [
                ['ok' => $meta['mime_valido'] ?? false, 'label' => 'Tipo MIME válido (imagen real)'],
                ['ok' => $meta['tamano_valido'] ?? false, 'label' => 'Tamaño dentro del límite (5 MB)'],
                ['ok' => $meta['exif_limpio'] ?? false, 'label' => ($meta['exif_limpio'] ?? false) ? 'Sin evidencia de edición (EXIF limpio)' : 'EXIF: posible edición detectada'],
                ['ok' => $meta['hash_unico'] ?? false, 'label' => 'Hash único (sin duplicados)'],
                ['ok' => $meta['no_duplicado'] ?? true, 'label' => 'No reutilizado en pedidos anteriores'],
                // Coherencia declarada por el cliente
                ['ok' => ($meta['coherencia']['monto_coincide'] ?? null), 'label' => 'Monto declarado coincide con el total del pedido'],
                ['ok' => ($meta['coherencia']['fecha_valida'] ?? null), 'label' => 'Fecha de pago declarada plausible (no futura ni anterior a creación)'],
                ['ok' => ($meta['coherencia']['referencia_unica'] ?? null), 'label' => 'Referencia no repetida en otros pedidos'],
            ];
            $allOk = collect($checks)->every(fn($c) => $c['ok'] === true);
        @endphp
        <div class="bg-[#131720] border border-[#2A3047] rounded-xl p-5">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <div class="font-bold text-[#F1F5F9]">Pedido {{ $payment->order?->numero_pedido ?? '-' }}</div>
                    <div class="text-xs text-[#94A3B8] mt-0.5">{{ $payment->order?->user?->name ?? '-' }}</div>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#EA580C]/10 text-[#FB923C]">Pendiente</span>
            </div>

            <div class="bg-[#1C2130] border border-[#2A3047] rounded-lg h-28 flex items-center justify-center mb-4">
                <span class="text-4xl opacity-50">📄</span>
            </div>

            <div class="grid grid-cols-2 gap-y-2 text-sm mb-4">
                <div class="text-[#94A3B8]">Método</div>
                <div class="text-[#F1F5F9] font-semibold text-right">{{ $payment->metodo }}</div>
                <div class="text-[#94A3B8]">Monto (servidor)</div>
                <div class="text-[#34D399] font-bold text-right">${{ number_format($payment->monto, 2) }}</div>
                <div class="text-[#94A3B8]">Monto declarado</div>
                <div class="text-[#F1F5F9] font-mono text-right">{{ isset($payment->monto_declarado) ? '$' . number_format($payment->monto_declarado, 2) : '-' }}</div>
                <div class="text-[#94A3B8]">Moneda declarada</div>
                <div class="text-[#F1F5F9] text-right">{{ $payment->moneda_declarada ?? '-' }}</div>
                <div class="text-[#94A3B8]">Referencia</div>
                <div class="text-[#F1F5F9] font-mono text-right">{{ $payment->numero_referencia ?? '-' }}</div>
                <div class="text-[#94A3B8]">Fecha declarada</div>
                <div class="text-[#F1F5F9] text-right">{{ $payment->fecha_pago_declarada ? $payment->fecha_pago_declarada->format('Y-m-d') : '-' }}</div>
                <div class="text-[#94A3B8]">Subido</div>
                <div class="text-[#F1F5F9] text-right">{{ $payment->created_at->format('Y-m-d H:i') }}</div>
            </div>

            <div class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wide mb-2">
                Validaciones automáticas
            </div>
            <div class="space-y-1.5 mb-4">
                @foreach($checks as $c)
                    <div class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm
                        {{ $c['ok'] ? 'bg-[#059669]/10 text-[#34D399]' : 'bg-[#EA580C]/10 text-[#FB923C]' }}">
                        <span>{{ $c['ok'] ? '✅' : '⚠️' }}</span>
                        <span>{{ $c['label'] }}</span>
                    </div>
                @endforeach
            </div>

            <div class="flex gap-2">
                <button wire:click="openReject({{ $payment->id }})"
                    class="flex-1 py-2.5 rounded-lg bg-[#DC2626]/10 text-[#F87171] font-semibold text-sm hover:bg-[#DC2626]/20">
                    ✕ Rechazar
                </button>
                <button wire:click="approve({{ $payment->id }})"
                    class="flex-1 py-2.5 rounded-lg bg-[#059669]/15 text-[#34D399] font-semibold text-sm hover:bg-[#059669]/25">
                    ✓ Aprobar pago
                </button>
            </div>

            @unless($allOk)
                <div class="text-[12px] text-[#FB923C] mt-2 text-center">
                    ⚠️ Se detectaron alertas. Revisa antes de aprobar.
                </div>
            @endunless
        </div>
    @empty
        <div class="col-span-2 text-center py-16 text-[#94A3B8]">
            <div class="text-5xl mb-3">✅</div>
            <div>No hay comprobantes pendientes de verificación</div>
        </div>
    @endforelse
</div>

<!-- Rechazo modal -->
<div x-data x-show="$wire.showRejectModal" class="fixed inset-0 bg-black/65 backdrop-blur-sm flex items-center justify-center p-4 z-50" style="display: none;">
    <div class="bg-[#2B3346] border border-[#384457] rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-xl">
        <div class="p-5 border-b border-[#2A3047] flex items-center justify-between">
            <h3 class="font-bold text-base text-[#F1F5F9]">Rechazar comprobante</h3>
            <button wire:click.prevent="$set('showRejectModal', false)" class="w-8 h-8 bg-[#1C2130] border border-[#2A3047] rounded-lg text-[#94A3B8]">✕</button>
        </div>
        <div class="p-6">
            <label class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wide mb-1.5 block">Motivo</label>
            <textarea wire:model="rejectReason" class="bg-[#1C2130] border border-[#2A3047] rounded-lg px-3 py-2.5 text-sm w-full text-[#F1F5F9]" rows="4"></textarea>
        </div>
        <div class="p-4 border-t border-[#2A3047] flex justify-end gap-2">
            <button wire:click.prevent="$set('showRejectModal', false)" class="px-4 py-2 rounded-lg bg-[#1C2130] border border-[#2A3047] text-[#94A3B8]">Cancelar</button>
            <button wire:click.prevent="reject" class="px-4 py-2 rounded-lg bg-gradient-to-r from-[#1D4ED8] to-[#0D9488] text-white font-semibold">Confirmar rechazo</button>
        </div>
    </div>
</div>
</div>
<div class="relative">
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    @forelse($payments as $payment)
        @php
            $meta = $payment->metadata_comprobante ?? [];
            $coh = $meta['coherencia'] ?? [];
            $ocr = ($meta['ocr']['disponible'] ?? false) ? $meta['ocr'] : null;
            $auto = $meta['autocompletado'] ?? [];
            $enBs = ($coh['moneda_comparada'] ?? null) === 'VES';
            $bs = fn ($n) => $n === null ? '-' : 'Bs ' . number_format((float) $n, 2, ',', '.');
            $metodos = ['transferencia' => 'Transferencia', 'pagomovil' => 'Pago móvil', 'binance' => 'Binance', 'fisico' => 'Efectivo'];

            // ok: true = verificado, false = alerta, null = sin dato. "requerido" convierte el null en alerta.
            $checks = [
                ['ok' => $meta['mime_valido'] ?? false, 'label' => 'Tipo de archivo válido (imagen o PDF real)', 'requerido' => true],
                ['ok' => $meta['tamano_valido'] ?? false, 'label' => 'Tamaño dentro del límite (5 MB)', 'requerido' => true],
                ['ok' => $meta['exif_limpio'] ?? false, 'label' => ($meta['exif_limpio'] ?? false) ? 'Sin evidencia de edición (EXIF limpio)' : 'EXIF: posible edición detectada', 'requerido' => true],
                ['ok' => $meta['hash_unico'] ?? false, 'label' => 'Archivo no subido antes (hash único)', 'requerido' => true],
                ['ok' => $coh['monto_coincide'] ?? null, 'requerido' => true, 'label' => $enBs
                    ? 'Monto en Bs = total × tasa BCV del día del pago' . (isset($coh['diferencia_pct']) ? ' (diferencia ' . number_format($coh['diferencia_pct'], 2, ',', '.') . ' %)' : '')
                    : 'Monto pagado coincide con el total del pedido'],
                ['ok' => $coh['fecha_valida'] ?? null, 'label' => 'Fecha de pago válida (no futura ni anterior al pedido)', 'requerido' => true],
                ['ok' => $coh['referencia_unica'] ?? null, 'label' => 'Referencia no usada en otro pedido', 'requerido' => true],
                ['ok' => $ocr ? (($coh['ocr_coincide'] ?? null) !== false) : null, 'requerido' => false, 'label' => ! $ocr
                    ? 'Lectura automática no disponible: verificar a ojo' . (isset($meta['ocr']['motivo']) ? ' (' . $meta['ocr']['motivo'] . ')' : '')
                    : (($coh['ocr_coincide'] ?? null) === false
                        ? 'Lo declarado no coincide con el comprobante: ' . implode(', ', $coh['ocr_diferencias'] ?? [])
                        : 'Datos leídos del comprobante (OCR)')],
            ];
            $alertas = collect($checks)->filter(fn ($c) => $c['ok'] === false || ($c['ok'] === null && $c['requerido']))->count();

            // Filas de datos: valor guardado + valor leído en el comprobante si difiere
            $filas = [
                ['Monto pagado', $payment->monto_declarado !== null ? ($enBs ? $bs($payment->monto_declarado) : '$' . number_format($payment->monto_declarado, 2)) : '-',
                    in_array('monto_declarado', $auto), ($ocr && $ocr['monto_bs'] !== null && $payment->monto_declarado !== null && abs($ocr['monto_bs'] - (float) $payment->monto_declarado) > 0.01) ? $bs($ocr['monto_bs']) : null],
                ['Fecha del pago', $payment->fecha_pago_declarada?->format('d/m/Y') ?? '-',
                    in_array('fecha_pago_declarada', $auto), ($ocr && $ocr['fecha'] && $payment->fecha_pago_declarada && $ocr['fecha'] !== $payment->fecha_pago_declarada->toDateString()) ? \Illuminate\Support\Carbon::parse($ocr['fecha'])->format('d/m/Y') : null],
                ['N.º de operación', $payment->numero_referencia ?? '-',
                    in_array('numero_referencia', $auto), ($ocr && $ocr['operacion'] && $payment->numero_referencia && ! str_ends_with($ocr['operacion'], preg_replace('/\D/', '', $payment->numero_referencia))) ? $ocr['operacion'] : null],
                ['Banco de origen', $payment->banco_origen ?? '-', in_array('banco_origen', $auto), null],
                ['Cédula del pagador', $payment->cedula_pagador ?? '-', in_array('cedula_pagador', $auto), null],
            ];
            if ($ocr && ! empty($ocr['nombre'])) {
                $filas[] = ['Titular (comprobante)', $ocr['nombre'], true, null];
            }
        @endphp
        <div class="bg-[#334155] border border-[#475569] rounded-xl p-5">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <div class="font-bold text-[#F1F5F9]">Pedido {{ $payment->order?->numero_pedido ?? '-' }}</div>
                    <div class="text-xs text-[#A0AEC0] mt-0.5">{{ $payment->order?->user?->name ?? '-' }}</div>
                </div>
                @if($payment->estado === 'sospechoso')
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#DC2626]/15 text-[#F87171]">Sospechoso</span>
                @else
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#EA580C]/10 text-[#FB923C]">Pendiente</span>
                @endif
            </div>

            @if($payment->ruta_comprobante)
                @php $receiptUrl = route('admin.payments.receipt', $payment); @endphp
                <a href="{{ $receiptUrl }}" target="_blank" rel="noopener"
                   class="block bg-[#475569] border border-[#475569] rounded-lg mb-4 overflow-hidden hover:border-[#94A3B8]"
                   title="Abrir comprobante en tamaño completo">
                    @if(str_starts_with($meta['mime'] ?? '', 'image/'))
                        <img src="{{ $receiptUrl }}" alt="Comprobante del pedido {{ $payment->order?->numero_pedido }}"
                             class="w-full h-64 object-contain bg-[#1E293B]" loading="lazy">
                    @else
                        <div class="h-28 flex flex-col items-center justify-center gap-1 text-[#F1F5F9]">
                            <span class="text-4xl">📄</span>
                            <span class="text-xs text-[#A0AEC0]">Ver PDF del comprobante</span>
                        </div>
                    @endif
                </a>
            @else
                <div class="bg-[#475569] border border-[#475569] rounded-lg h-28 flex items-center justify-center mb-4 text-sm text-[#FB923C]">
                    Sin comprobante adjunto
                </div>
            @endif

            <div class="bg-[#1E293B] border border-[#475569] rounded-lg p-3 mb-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-[#A0AEC0]">Total del pedido</span>
                    <span class="text-[#34D399] font-bold">${{ number_format($payment->monto, 2) }}</span>
                </div>
                @if($enBs)
                    <div class="flex justify-between mt-1">
                        <span class="text-[#A0AEC0]">Debió pagar</span>
                        <span class="text-[#F1F5F9] font-bold">{{ $bs($coh['monto_bs_esperado'] ?? null) }}</span>
                    </div>
                    <div class="text-[11px] text-[#A0AEC0] text-right mt-0.5">
                        Tasa BCV {{ number_format($coh['tasa_bcv'] ?? 0, 2, ',', '.') }} Bs/USD del día del pago
                    </div>
                @elseif(in_array($payment->metodo, ['transferencia', 'pagomovil']))
                    <div class="text-[11px] text-[#FB923C] text-right mt-1">Sin tasa BCV cargada: no se pudo calcular el monto en Bs</div>
                @endif
            </div>

            <div class="grid grid-cols-2 gap-y-2 text-sm mb-4">
                <div class="text-[#A0AEC0]">Método</div>
                <div class="text-[#F1F5F9] font-semibold text-right">{{ $metodos[$payment->metodo] ?? $payment->metodo }}</div>
                @foreach($filas as [$label, $valor, $leido, $enComprobante])
                    <div class="text-[#A0AEC0]">{{ $label }}</div>
                    <div class="text-right">
                        <span class="text-[#F1F5F9] font-mono">{{ $valor }}</span>
                        @if($leido)
                            <div class="text-[10px] text-[#60A5FA]">leído del comprobante</div>
                        @endif
                        @if($enComprobante)
                            <div class="text-[11px] text-[#FB923C]">comprobante: {{ $enComprobante }}</div>
                        @endif
                    </div>
                @endforeach
                <div class="text-[#A0AEC0]">Subido</div>
                <div class="text-[#F1F5F9] text-right">{{ $payment->created_at->format('d/m/Y H:i') }}</div>
            </div>

            <div class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wide mb-2">
                Validaciones automáticas
            </div>
            <div class="space-y-1.5 mb-4">
                @foreach($checks as $c)
                    @php $alerta = $c['ok'] === false || ($c['ok'] === null && $c['requerido']); @endphp
                    <div class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm
                        {{ $c['ok'] === true ? 'bg-[#059669]/10 text-[#34D399]' : ($alerta ? 'bg-[#EA580C]/10 text-[#FB923C]' : 'bg-[#475569]/40 text-[#A0AEC0]') }}">
                        <span>{{ $c['ok'] === true ? '✅' : ($alerta ? '⚠️' : '➖') }}</span>
                        <span>{{ $c['label'] }}{{ $c['ok'] === null && $c['requerido'] ? ' — sin dato' : '' }}</span>
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

            @if($alertas > 0)
                <div class="text-[12px] text-[#FB923C] mt-2 text-center">
                    ⚠️ {{ $alertas }} {{ $alertas === 1 ? 'alerta' : 'alertas' }}. Revisa el comprobante antes de aprobar.
                </div>
            @endif
        </div>
    @empty
        <div class="col-span-2 text-center py-16 text-[#A0AEC0]">
            <div class="text-5xl mb-3">✅</div>
            <div>No hay comprobantes pendientes de verificación</div>
        </div>
    @endforelse
</div>

<!-- Rechazo modal -->
<div x-data x-show="$wire.showRejectModal" class="fixed inset-0 bg-black/65 backdrop-blur-sm flex items-center justify-center p-4 z-50" style="display: none;">
    <div class="bg-[#2B3346] border border-[#384457] rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-xl">
        <div class="p-5 border-b border-[#475569] flex items-center justify-between">
            <h3 class="font-bold text-base text-[#F1F5F9]">Rechazar comprobante</h3>
            <button wire:click.prevent="$set('showRejectModal', false)" class="w-8 h-8 bg-[#475569] border border-[#475569] rounded-lg text-[#A0AEC0]">✕</button>
        </div>
        <div class="p-6">
            <label class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wide mb-1.5 block">Motivo</label>
            <textarea wire:model="rejectReason" class="bg-[#475569] border border-[#475569] rounded-lg px-3 py-2.5 text-sm w-full text-[#F1F5F9]" rows="4"></textarea>
        </div>
        <div class="p-4 border-t border-[#475569] flex justify-end gap-2">
            <button wire:click.prevent="$set('showRejectModal', false)" class="px-4 py-2 rounded-lg bg-[#475569] border border-[#475569] text-[#A0AEC0]">Cancelar</button>
            <button wire:click.prevent="reject" class="px-4 py-2 rounded-lg bg-gradient-to-r from-[#1D4ED8] to-[#0D9488] text-white font-semibold">Confirmar rechazo</button>
        </div>
    </div>
</div>
</div>
<div class="row">
    @foreach($payments as $payment)
        @php $c = $checks[$payment->id] ?? null; @endphp
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Pedido {{ optional($payment->order)->numero_pedido }}</h5>
                    <p class="card-text">
                        <strong>Cliente:</strong> {{ optional(optional($payment->order)->user)->name }}<br>
                        <strong>Monto:</strong> ${{ number_format($payment->monto, 2) }}<br>
                        <strong>Método:</strong> {{ $payment->metodo }}<br>
                        <strong>Fecha:</strong> {{ $payment->created_at }}
                    </p>

                    @if($payment->ruta_comprobante)
                        <p><a href="{{ route('admin.pagos.receipt', $payment->id) }}" target="_blank">Ver comprobante</a></p>
                    @endif

                    <ul class="list-unstyled">
                        @if($c)
                            <li>{{ $c['mime_valido'] ? '✅' : '❌' }} MIME válido</li>
                            <li>{{ $c['tamano_valido'] ? '✅' : '❌' }} Tamaño válido</li>
                            <li>{{ $c['exif_limpio'] ? '✅' : '⚠️' }} EXIF limpio</li>
                            <li>{{ $c['hash_unico'] ? '✅' : '⚠️' }} Hash único</li>
                            <li>{{ $c['no_duplicado'] ? '✅' : '⚠️' }} No duplicado</li>
                        @else
                            <li>No hay comprobante para validar</li>
                        @endif
                    </ul>

                    <div>
                        @php
                            $level = $c['nivel_riesgo'] ?? 'high';
                        @endphp
                        @if($level === 'low')
                            <span class="badge bg-success">LOW</span>
                        @elseif($level === 'medium')
                            <span class="badge bg-warning">MEDIUM</span>
                        @else
                            <span class="badge bg-danger">HIGH</span>
                        @endif
                    </div>

                    <div class="mt-3 d-flex">
                        <button wire:click="approve({{ $payment->id }})" class="btn btn-sm btn-success me-2">Aprobar</button>

                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $payment->id }}">Rechazar</button>
                    </div>
                </div>
            </div>

            <!-- Rechazo modal -->
            <div wire:ignore.self class="modal fade" id="rejectModal{{ $payment->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Rechazar comprobante</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label>Motivo</label>
                                <textarea id="reason{{ $payment->id }}" class="form-control"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-danger" onclick="
                                var reason = document.getElementById('reason' + {{ $payment->id }}).value;
                                Livewire.emit('reject', {{ $payment->id }}, reason);
                                var modal = bootstrap.Modal.getInstance(document.getElementById('rejectModal' + {{ $payment->id }}));
                                modal.hide();
                            ">Rechazar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<script>
    // Escuchar evento desde Livewire para refrescar la lista
    Livewire.on('payment-updated', () => {
        Livewire.emit('refresh');
    });
</script>
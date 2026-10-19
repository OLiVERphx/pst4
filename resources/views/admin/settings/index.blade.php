@extends('layouts.admin')

@section('pageTitle', 'Configuración')

@section('content')
<div class="container mx-auto py-6">
    <h1 class="text-2xl font-semibold mb-4 text-[#F1F5F9]">Configuración</h1>

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-emerald-500/40 bg-emerald-500/10 p-3 text-sm text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-500/40 bg-red-500/10 p-3 text-sm text-red-200">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $empresa = \App\Models\Configuracion::empresa();
        $pago = \App\Models\Configuracion::datosPago();
        $disponibles = \App\Models\Configuracion::metodosPagoDisponibles();
        $bancos = config('pagos.bancos');
        $campo = 'w-full bg-[#475569] border border-[#384457] rounded-lg px-3 py-2 text-sm text-[#F1F5F9] placeholder-[#94A3B8]';
        $etiqueta = 'block text-sm text-[#A0AEC0] mb-1';
        $estado = fn ($ok) => $ok
            ? '<span class="ml-2 rounded-full bg-[#059669]/15 px-2 py-0.5 text-[11px] font-bold text-[#34D399]">Visible en el checkout</span>'
            : '<span class="ml-2 rounded-full bg-[#475569] px-2 py-0.5 text-[11px] font-bold text-[#CBD5E1]">Oculto: faltan datos</span>';
    @endphp

    <form method="POST" action="{{ route('admin.configuracion.datos_tienda') }}" class="mb-6 bg-[#334155] border border-[#475569] rounded-xl p-5 space-y-6">
        @csrf
        <div>
            <h3 class="font-bold text-[#F1F5F9]">Datos de la empresa y de pago</h3>
            <p class="text-sm text-[#A0AEC0]">Es lo que ve el cliente al pagar. Un método de pago en línea solo aparece en el checkout cuando tiene sus datos completos.</p>
        </div>

        <fieldset class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <legend class="mb-2 text-xs font-semibold uppercase tracking-wide text-[#A0AEC0]">Empresa</legend>
            <div>
                <label class="{{ $etiqueta }}" for="empresa_nombre">Razón social</label>
                <input id="empresa_nombre" name="empresa[nombre]" value="{{ old('empresa.nombre', $empresa['nombre']) }}" class="{{ $campo }}" placeholder="Smartphone World C.A." required />
            </div>
            <div>
                <label class="{{ $etiqueta }}" for="empresa_rif">RIF</label>
                <input id="empresa_rif" name="empresa[rif]" value="{{ old('empresa.rif', $empresa['rif']) }}" class="{{ $campo }}" placeholder="J-12345678-9" required />
            </div>
            <div>
                <label class="{{ $etiqueta }}" for="empresa_telefono">Teléfono de contacto</label>
                <input id="empresa_telefono" name="empresa[telefono]" value="{{ old('empresa.telefono', $empresa['telefono']) }}" class="{{ $campo }}" placeholder="0271-0000000" />
            </div>
            <div>
                <label class="{{ $etiqueta }}" for="empresa_direccion">Dirección</label>
                <input id="empresa_direccion" name="empresa[direccion]" value="{{ old('empresa.direccion', $empresa['direccion']) }}" class="{{ $campo }}" placeholder="Valera, estado Trujillo" />
            </div>
        </fieldset>

        <fieldset class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <legend class="mb-2 text-xs font-semibold uppercase tracking-wide text-[#A0AEC0]">🏦 Transferencia bancaria {!! $estado($disponibles['transferencia']) !!}</legend>
            <div>
                <label class="{{ $etiqueta }}" for="tr_banco">Banco</label>
                <select id="tr_banco" name="pago[transferencia][banco]" class="{{ $campo }}">
                    <option value="">— Sin configurar —</option>
                    @foreach($bancos as $codigo => $nombre)
                        <option value="{{ $codigo }}" @selected(old('pago.transferencia.banco', $pago['transferencia']['banco']) === (string) $codigo)>{{ $codigo }} - {{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $etiqueta }}" for="tr_tipo">Tipo de cuenta</label>
                <select id="tr_tipo" name="pago[transferencia][tipo_cuenta]" class="{{ $campo }}">
                    <option value="corriente" @selected(old('pago.transferencia.tipo_cuenta', $pago['transferencia']['tipo_cuenta']) === 'corriente')>Corriente</option>
                    <option value="ahorro" @selected(old('pago.transferencia.tipo_cuenta', $pago['transferencia']['tipo_cuenta']) === 'ahorro')>Ahorro</option>
                </select>
            </div>
            <div>
                <label class="{{ $etiqueta }}" for="tr_cuenta">Número de cuenta (20 dígitos)</label>
                <input id="tr_cuenta" name="pago[transferencia][numero_cuenta]" value="{{ old('pago.transferencia.numero_cuenta', $pago['transferencia']['numero_cuenta']) }}" class="{{ $campo }}" inputmode="numeric" placeholder="0102 0000 00 0000000000" />
            </div>
            <div>
                <label class="{{ $etiqueta }}" for="tr_titular">Titular</label>
                <input id="tr_titular" name="pago[transferencia][titular]" value="{{ old('pago.transferencia.titular', $pago['transferencia']['titular']) }}" class="{{ $campo }}" placeholder="Smartphone World C.A." />
            </div>
            <div>
                <label class="{{ $etiqueta }}" for="tr_doc">Cédula o RIF del titular</label>
                <input id="tr_doc" name="pago[transferencia][documento]" value="{{ old('pago.transferencia.documento', $pago['transferencia']['documento']) }}" class="{{ $campo }}" placeholder="J-12345678-9" />
            </div>
        </fieldset>

        <fieldset class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <legend class="mb-2 text-xs font-semibold uppercase tracking-wide text-[#A0AEC0]">📱 Pago móvil {!! $estado($disponibles['pagomovil']) !!}</legend>
            <div>
                <label class="{{ $etiqueta }}" for="pm_banco">Banco</label>
                <select id="pm_banco" name="pago[pagomovil][banco]" class="{{ $campo }}">
                    <option value="">— Sin configurar —</option>
                    @foreach($bancos as $codigo => $nombre)
                        <option value="{{ $codigo }}" @selected(old('pago.pagomovil.banco', $pago['pagomovil']['banco']) === (string) $codigo)>{{ $codigo }} - {{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $etiqueta }}" for="pm_tel">Teléfono</label>
                <input id="pm_tel" name="pago[pagomovil][telefono]" value="{{ old('pago.pagomovil.telefono', $pago['pagomovil']['telefono']) }}" class="{{ $campo }}" inputmode="tel" placeholder="0414-0000000" />
            </div>
            <div>
                <label class="{{ $etiqueta }}" for="pm_doc">Cédula o RIF</label>
                <input id="pm_doc" name="pago[pagomovil][documento]" value="{{ old('pago.pagomovil.documento', $pago['pagomovil']['documento']) }}" class="{{ $campo }}" placeholder="J-12345678-9" />
            </div>
        </fieldset>

        <fieldset class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <legend class="mb-2 text-xs font-semibold uppercase tracking-wide text-[#A0AEC0]">💱 Binance (USDT) {!! $estado($disponibles['binance']) !!}</legend>
            <div>
                <label class="{{ $etiqueta }}" for="bn_id">Binance Pay ID</label>
                <input id="bn_id" name="pago[binance][pay_id]" value="{{ old('pago.binance.pay_id', $pago['binance']['pay_id']) }}" class="{{ $campo }}" inputmode="numeric" placeholder="123456789" />
            </div>
            <div>
                <label class="{{ $etiqueta }}" for="bn_correo">Correo de Binance (opcional)</label>
                <input id="bn_correo" type="email" name="pago[binance][correo]" value="{{ old('pago.binance.correo', $pago['binance']['correo']) }}" class="{{ $campo }}" />
            </div>
        </fieldset>

        <p class="text-xs text-[#A0AEC0]">Para dejar de ofrecer un método, borra sus datos y guarda. El pago en efectivo al retirar en tienda siempre está disponible.</p>
        <div class="text-right">
            <button type="submit" class="px-4 py-2 rounded-lg bg-gradient-to-r from-[#1D4ED8] to-[#0D9488] text-white font-semibold">Guardar datos de la empresa y de pago</button>
        </div>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @include('admin.perfil._password')
        @include('admin.perfil._notificaciones')

        <!-- Sistema -->
        <div class="bg-[#334155] border border-[#475569] rounded-xl p-5">
            <h3 class="font-bold text-[#F1F5F9] mb-2">Sistema</h3>
            <div class="text-sm space-y-2 text-[#F1F5F9]">
                <div>Versión: 1.0.0</div>
                <div>Laravel: {{ app()->version() }}</div>
                <div>PHP: {{ PHP_VERSION }}</div>
                <div>Base de datos: {{ $baseDeDatos ?? '—' }}</div>
                <div>Total productos: {{ $productCount ?? 0 }}</div>
                <div>Total pedidos: {{ $orderCount ?? 0 }}</div>
                <div>Total clientes: {{ $totalClients ?? 0 }}</div>
            </div>

            <form method="POST" action="{{ route('admin.settings.limpiar_cache') }}" class="mt-4 flex items-center justify-between gap-3"
                  onsubmit="return confirm('¿Limpiar la caché? Las tasas, reportes y el índice de búsqueda se volverán a calcular.')">
                @csrf
                <p class="text-xs text-[#A0AEC0]">Úsalo si un cambio no se refleja (por ejemplo, productos que no aparecen en la búsqueda).</p>
                <button type="submit" class="shrink-0 px-4 py-2 rounded-lg bg-[#475569] border border-[#475569] text-sm text-[#F1F5F9] hover:bg-[#3B4A61]">Limpiar caché</button>
            </form>
        </div>
    </div>

    <div class="mt-6 bg-[#334155] border border-[#475569] rounded-xl p-5">
        <h3 class="font-bold text-[#F1F5F9] mb-4">Configuración general</h3>

        <form method="POST" action="{{ route('admin.configuracion.update') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-[#A0AEC0] mb-1">Tasa BCV (Bs por USD)</label>
                    <input type="number" step="any" name="tasa_bcv" value="{{ old('tasa_bcv', $config->tasa_bcv ?? '') }}" class="w-full bg-[#475569] border border-[#384457] rounded-lg px-3 py-2 text-sm text-[#F1F5F9]" />
                    @php $ultimaTasa = \App\Models\TasaBcv::vigenteEn(); @endphp
                    <p class="text-xs text-[#A0AEC0] mt-1">
                        @if($ultimaTasa)
                            Vigente desde {{ $ultimaTasa->fecha->format('d/m/Y') }} ({{ $ultimaTasa->fuente === 'dolarapi' ? 'automática' : 'manual' }}). Se actualiza sola cada hora.
                        @else
                            Aún no hay historial de tasas: carga la de hoy.
                        @endif
                    </p>
                    @error('tasa_bcv')<p class="text-xs text-[#F87171] mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm text-[#A0AEC0] mb-1">Fecha de la tasa BCV</label>
                    <input type="date" name="fecha_tasa_bcv" value="{{ old('fecha_tasa_bcv', now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="w-full bg-[#475569] border border-[#384457] rounded-lg px-3 py-2 text-sm text-[#F1F5F9]" />
                    <p class="text-xs text-[#A0AEC0] mt-1">Solo cámbiala para corregir la tasa de un día anterior.</p>
                    @error('fecha_tasa_bcv')<p class="text-xs text-[#F87171] mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm text-[#A0AEC0] mb-1">Tasa Binance</label>
                    <input type="number" step="any" name="tasa_binance" value="{{ old('tasa_binance', $config->tasa_binance ?? '') }}" class="w-full bg-[#475569] border border-[#384457] rounded-lg px-3 py-2 text-sm text-[#F1F5F9]" />
                </div>

                <div>
                    <label class="block text-sm text-[#A0AEC0] mb-1">Mínimo compra mayorista (USD)</label>
                    <input type="number" step="any" name="minimo_compra_mayor_usd" value="{{ old('minimo_compra_mayor_usd', $config->minimo_compra_mayor_usd ?? '') }}" class="w-full bg-[#475569] border border-[#384457] rounded-lg px-3 py-2 text-sm text-[#F1F5F9]" />
                </div>

                <div>
                    <label class="block text-sm text-[#A0AEC0] mb-1">Mínimo unidades mayorista</label>
                    <input type="number" step="1" name="minimo_unidades_mayor" value="{{ old('minimo_unidades_mayor', $config->minimo_unidades_mayor ?? '') }}" class="w-full bg-[#475569] border border-[#384457] rounded-lg px-3 py-2 text-sm text-[#F1F5F9]" />
                </div>
            </div>

            <div class="mt-4 text-right">
                <button type="submit" class="px-4 py-2 rounded-lg bg-[#475569] border border-[#475569] text-[#F1F5F9]">Guardar</button>
            </div>
        </form>
    </div>
</div>
@endsection

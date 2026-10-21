<div class="space-y-4">
    @php
        $pasos = [1 => 'Subir archivo', 2 => 'Asignar columnas', 3 => 'Vista previa', 4 => 'Resultado'];
        $tarjeta = 'bg-[#334155] border border-[#475569] rounded-xl p-4 sm:p-5';
        $campoForm = 'bg-[#475569] border border-[#475569] rounded-lg px-3 py-1.5 text-sm text-[#F1F5F9] focus:border-[#1D4ED8] w-full';
        $botonPrincipal = 'bg-[#1D4ED8] hover:bg-[#1e40af] disabled:opacity-50 text-white text-sm font-semibold px-4 py-2 rounded-lg transition';
        $botonSecundario = 'border border-[#475569] hover:bg-[#475569] text-[#F1F5F9] text-sm px-4 py-2 rounded-lg transition';
    @endphp

    {{-- Pasos --}}
    <ol class="flex flex-wrap gap-2 text-xs">
        @foreach($pasos as $numero => $titulo)
            <li class="px-3 py-1 rounded-full border {{ $paso === $numero ? 'bg-[#1D4ED8] border-[#1D4ED8] text-white' : ($paso > $numero ? 'border-[#059669] text-[#059669]' : 'border-[#475569] text-[#A0AEC0]') }}">
                {{ $numero }}. {{ $titulo }}
            </li>
        @endforeach
    </ol>

    @if($errors->any())
        <div class="bg-red-500/10 border border-red-500/30 rounded-lg px-4 py-3 text-sm text-red-400 space-y-1">
            @foreach($errors->all() as $mensaje)
                <div>{{ $mensaje }}</div>
            @endforeach
        </div>
    @endif

    {{-- Paso 1: subir --}}
    @if($paso === 1)
        <div class="{{ $tarjeta }} space-y-4">
            <div class="text-sm text-[#CBD5E1] space-y-1">
                <p>Puede subir el Excel que ya usa la tienda: en el siguiente paso se indica qué columna es cada dato.</p>
                <p class="text-[#A0AEC0]">Requisitos: una fila por producto, una fila de encabezados y sin celdas combinadas entre los productos. Máximo 5 MB y {{ number_format(\App\Services\Importacion\LectorHojaCalculo::MAX_FILAS, 0, ',', '.') }} filas.</p>
            </div>

            <form wire:submit="subir" class="flex flex-col sm:flex-row sm:items-center gap-3">
                <input type="file" wire:model="archivo" accept=".xlsx,.xls,.ods,.csv"
                       class="text-sm text-[#CBD5E1] file:mr-3 file:rounded-lg file:border-0 file:bg-[#475569] file:px-3 file:py-2 file:text-[#F1F5F9] w-full sm:w-auto" />
                <button type="submit" class="{{ $botonPrincipal }}" wire:loading.attr="disabled" wire:target="archivo,subir">
                    <span wire:loading.remove wire:target="subir">Leer archivo</span>
                    <span wire:loading wire:target="subir">Leyendo...</span>
                </button>
            </form>
            <div wire:loading wire:target="archivo" class="text-xs text-[#A0AEC0]">Subiendo archivo...</div>

            <div class="pt-3 border-t border-[#475569] text-sm text-[#A0AEC0]">
                ¿No tiene un formato propio?
                <a href="{{ route('admin.productos.importar.plantilla') }}" class="text-[#60A5FA] hover:underline">Descargue la plantilla</a>
                con los encabezados que el sistema reconoce directamente.
            </div>
        </div>
    @endif

    {{-- Paso 2: asignar columnas --}}
    @if($paso === 2)
        <div class="{{ $tarjeta }} space-y-5">
            <div class="text-sm text-[#CBD5E1]">Archivo: <span class="font-semibold text-[#F1F5F9]">{{ $nombreArchivo }}</span></div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @if(count($hojas) > 1)
                    <label class="text-xs text-[#A0AEC0] space-y-1">
                        <span>Hoja</span>
                        <select wire:model.live="hoja" class="{{ $campoForm }}">
                            @foreach($hojas as $i => $nombreHoja)
                                <option value="{{ $i }}">{{ $nombreHoja }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                <label class="text-xs text-[#A0AEC0] space-y-1">
                    <span>Fila de encabezados</span>
                    <select wire:model.live="filaEncabezados" class="{{ $campoForm }}">
                        @foreach($filasDisponibles as $numeroFila)
                            <option value="{{ $numeroFila }}">Fila {{ $numeroFila }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-[#F1F5F9] mb-1">¿Qué columna corresponde a cada dato?</h3>
                <p class="text-xs text-[#A0AEC0] mb-3">Las sugerencias salen de los encabezados del archivo; revíselas. Los marcados con * son obligatorios para crear productos.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($campos as $campo => $definicion)
                        @php $bloqueado = $campo === 'stock' && !$puedeAjustarStock; @endphp
                        <label class="text-xs text-[#A0AEC0] space-y-1">
                            <span>{{ $definicion['etiqueta'] }}{{ $definicion['requerido'] ? ' *' : '' }}</span>
                            <select wire:model="mapeo.{{ $campo }}" class="{{ $campoForm }} {{ ($mapeo[$campo] ?? '') !== '' ? '!border-[#059669]' : '' }}" @disabled($bloqueado)>
                                <option value="">— No importar —</option>
                                @foreach($encabezados as $columna => $texto)
                                    <option value="{{ $columna }}">{{ $texto }}</option>
                                @endforeach
                            </select>
                            @if($bloqueado)
                                <span class="block text-[11px]">Requiere permiso de ajustar inventario.</span>
                            @endif
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3 border-t border-[#475569]">
                <label class="text-xs text-[#A0AEC0] space-y-1">
                    <span>Qué hacer</span>
                    <select wire:model="modo" class="{{ $campoForm }}">
                        <option value="crear_actualizar">Crear nuevos y actualizar existentes</option>
                        <option value="solo_crear">Solo crear nuevos</option>
                        <option value="solo_actualizar">Solo actualizar existentes</option>
                    </select>
                </label>
                <label class="text-xs text-[#A0AEC0] space-y-1">
                    <span>Los precios del archivo están en</span>
                    <select wire:model="moneda" class="{{ $campoForm }}">
                        <option value="USD">Dólares (USD)</option>
                        <option value="VES">Bolívares (se convierten con la tasa BCV)</option>
                    </select>
                </label>
                <label class="text-xs text-[#A0AEC0] flex items-center gap-2 sm:pt-5">
                    <input type="checkbox" wire:model="crearFaltantes" class="rounded border-[#475569] bg-[#475569]" />
                    <span>Crear marcas y categorías que no existan</span>
                </label>
            </div>

            <div class="flex flex-wrap gap-2 justify-end">
                <button wire:click="reiniciar" class="{{ $botonSecundario }}">Cambiar archivo</button>
                <button wire:click="analizar" class="{{ $botonPrincipal }}" wire:loading.attr="disabled" wire:target="analizar">
                    <span wire:loading.remove wire:target="analizar">Ver vista previa</span>
                    <span wire:loading wire:target="analizar">Revisando filas...</span>
                </button>
            </div>
        </div>
    @endif

    {{-- Paso 3: vista previa --}}
    @if($paso === 3 && $analisis)
        @php $r = $analisis['resumen']; @endphp
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            @foreach([['Se crearán', $r['crear'], 'text-[#059669]'], ['Se actualizarán', $r['actualizar'], 'text-[#60A5FA]'], ['Se omitirán', $r['omitir'], 'text-[#A0AEC0]'], ['Con error', $r['error'], 'text-red-400']] as [$titulo, $valor, $color])
                <div class="{{ $tarjeta }} !p-3">
                    <div class="text-[11px] uppercase tracking-wider text-[#A0AEC0]">{{ $titulo }}</div>
                    <div class="text-2xl font-bold {{ $color }}">{{ $valor }}</div>
                </div>
            @endforeach
        </div>

        @if($analisis['marcas_nuevas'] || $analisis['categorias_nuevas'])
            <div class="bg-amber-500/10 border border-amber-500/30 rounded-lg px-4 py-3 text-sm text-amber-300 space-y-1">
                @if($analisis['marcas_nuevas'])
                    <div>Marcas nuevas: {{ implode(', ', $analisis['marcas_nuevas']) }}</div>
                @endif
                @if($analisis['categorias_nuevas'])
                    <div>Categorías nuevas: {{ implode(', ', $analisis['categorias_nuevas']) }}</div>
                @endif
                <div class="text-xs text-amber-200/80">No existen en el sistema y se crearán al importar. Si son la misma que una existente con otro nombre (p. ej. "Teléfonos" y "Smartphones"), corrija el archivo o desmarque "Crear marcas y categorías".</div>
            </div>
        @endif

        <div class="bg-[#334155] border border-[#475569] rounded-xl overflow-x-auto">
            <table class="min-w-full divide-y divide-[#475569] text-sm">
                <thead class="bg-[#475569]">
                    <tr>
                        @foreach(['Fila', 'Acción', 'Código', 'Nombre', 'Precio detal', 'Stock', 'Observaciones'] as $th)
                            <th class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-3 py-2 text-left whitespace-nowrap">{{ $th }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#475569]">
                    @foreach($analisis['filas'] as $fila)
                        @php
                            $insignia = ['crear' => 'bg-[#059669]/10 text-[#059669]', 'actualizar' => 'bg-[#1D4ED8]/10 text-[#60A5FA]', 'omitir' => 'bg-[#475569] text-[#A0AEC0]', 'error' => 'bg-red-500/10 text-red-400'][$fila['accion']];
                        @endphp
                        <tr class="{{ $fila['accion'] === 'error' ? 'bg-red-500/5' : '' }}">
                            <td class="px-3 py-2 text-[#A0AEC0]">{{ $fila['fila'] }}</td>
                            <td class="px-3 py-2"><span class="px-2 py-0.5 rounded-full text-xs {{ $insignia }}">{{ ucfirst($fila['accion']) }}</span></td>
                            <td class="px-3 py-2 text-[#F1F5F9] whitespace-nowrap">{{ $fila['codigo'] }}</td>
                            <td class="px-3 py-2 text-[#F1F5F9] min-w-[12rem]">{{ $fila['nombre'] }}</td>
                            <td class="px-3 py-2 text-[#F1F5F9] whitespace-nowrap">{{ isset($fila['datos']['precio_detal']) ? '$' . number_format($fila['datos']['precio_detal'], 2) : '' }}</td>
                            <td class="px-3 py-2 text-[#F1F5F9]">{{ $fila['datos']['stock'] ?? '' }}</td>
                            <td class="px-3 py-2 text-xs min-w-[16rem]">
                                @foreach($fila['errores'] as $e)<div class="text-red-400">{{ $e }}</div>@endforeach
                                @foreach($fila['avisos'] as $a)<div class="text-amber-300">{{ $a }}</div>@endforeach
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($analisis['total_filas'] > count($analisis['filas']))
            <p class="text-xs text-[#A0AEC0]">Se muestran {{ count($analisis['filas']) }} de {{ $analisis['total_filas'] }} filas (primero las que tienen error).</p>
        @endif

        <div class="flex flex-wrap gap-2 justify-end">
            <button wire:click="volverAMapeo" class="{{ $botonSecundario }}">Volver a las columnas</button>
            @if($r['error'] > 0)
                <button wire:click="descargarErrores" class="{{ $botonSecundario }}">Descargar filas con error</button>
            @endif
            @if($r['crear'] + $r['actualizar'] > 0)
                <button wire:click="importar"
                        wire:confirm="Productos nuevos: {{ $r['crear'] }}. Productos actualizados: {{ $r['actualizar'] }}.{{ $r['error'] ? ' Filas con error que no se importan: ' . $r['error'] . '.' : '' }} ¿Continuar?"
                        class="{{ $botonPrincipal }}" wire:loading.attr="disabled" wire:target="importar">
                    <span wire:loading.remove wire:target="importar">Importar {{ $r['crear'] + $r['actualizar'] }} productos</span>
                    <span wire:loading wire:target="importar">Importando...</span>
                </button>
            @endif
        </div>
    @endif

    {{-- Paso 4: resultado --}}
    @if($paso === 4 && $resultado)
        <div class="{{ $tarjeta }} space-y-3">
            <div class="text-lg font-semibold text-[#059669]">Importación completada</div>
            <ul class="text-sm text-[#CBD5E1] space-y-1">
                <li>Productos creados: <b>{{ $resultado['creados'] }}</b></li>
                <li>Productos actualizados: <b>{{ $resultado['actualizados'] }}</b></li>
                <li>Movimientos de inventario registrados: <b>{{ $resultado['movimientos'] }}</b> (referencia {{ $resultado['referencia'] }})</li>
                @if($resultado['omitidos'])<li>Filas omitidas: {{ $resultado['omitidos'] }}</li>@endif
                @if($resultado['con_error'])<li class="text-red-400">Filas con error no importadas: {{ $resultado['con_error'] }}</li>@endif
            </ul>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.productos.lista') }}" class="{{ $botonPrincipal }}">Ver productos</a>
                <button wire:click="reiniciar" class="{{ $botonSecundario }}">Importar otro archivo</button>
            </div>
        </div>
    @endif
</div>

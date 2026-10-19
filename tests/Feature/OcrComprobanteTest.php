<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\Test;
use App\Models\User;
use App\Models\Product;
use App\Models\Payment;
use App\Services\ServicioOcrComprobantes;
use App\Services\ServicioPrecios;
use App\Services\ServicioValidacionPagos;

/**
 * Lectura de comprobantes BDV y validación del monto en bolívares con la
 * tasa BCV del día del pago. Datos de ejemplo inventados.
 */
class OcrComprobanteTest extends TestCase
{
    use RefreshDatabase;

    private const TEXTO_PAGO_MOVIL = "PagomóvilBDV Personas\n12.000,00 Bs\nFecha: 16/10/2026\nOperación: 001122334455 [[]\n"
        . "Identificación: 12345678\nOrigen: 0102****0000\nDestino: 04140000000\nBanco: 0102 - BANCO DE VENEZUELA\nConcepto: compra";

    private const TEXTO_TRANSFERENCIA = "Comprobante de operación\nTransferencias a terceros\n146.800.00 Bs\nFecha: 16/10/2026\n"
        . "Operación: 009988776655 0\nNombre: Cliente De Prueba\nOrigen: 0102****1111\nDestino: 0102****2222\nConcepto: pago";

    #[Test]
    public function extrae_los_campos_de_un_pago_movil_bdv()
    {
        $c = ServicioOcrComprobantes::extraerCampos(self::TEXTO_PAGO_MOVIL);

        $this->assertSame('pagomovil', $c['tipo']);
        $this->assertSame(12000.0, $c['monto_bs']);
        $this->assertSame('2026-10-16', $c['fecha']);
        $this->assertSame('001122334455', $c['operacion']);
        $this->assertSame('12345678', $c['identificacion']);
        $this->assertSame('0102 - Banco de Venezuela', $c['banco']);
    }

    #[Test]
    public function extrae_los_campos_de_una_transferencia_aunque_el_ocr_confunda_la_coma()
    {
        $c = ServicioOcrComprobantes::extraerCampos(self::TEXTO_TRANSFERENCIA);

        $this->assertSame('transferencia', $c['tipo']);
        $this->assertSame(146800.0, $c['monto_bs']);
        // El icono de copiar que sigue al número no se pega a la operación
        $this->assertSame('009988776655', $c['operacion']);
        $this->assertSame('Cliente De Prueba', $c['nombre']);
    }

    #[Test]
    public function el_monto_tolera_los_errores_tipicos_del_ocr()
    {
        // Variantes vistas en distintas versiones de Tesseract (Linux y Windows)
        $casos = [
            '1 00,00 Bs' => 100.0,   // dígitos separados
            '100, 00 Bs' => 100.0,
            '100,00 8s' => 100.0,    // "Bs" leído como "8s"
            '1OO,OO Bs' => 100.0,    // O en vez de 0
            '146 800,00 Bs' => 146800.0,
            'Bs. 2.500,50' => 2500.5,
            '0,00 Bs' => null,       // un monto en cero no es un pago
        ];
        foreach ($casos as $linea => $esperado) {
            $c = ServicioOcrComprobantes::extraerCampos("PagomovilBDV Personas\n{$linea}\nFecha: 16/10/2026");
            $this->assertSame($esperado, $c['monto_bs'], "Monto mal leído en: {$linea}");
        }
    }

    private function pagoBs(float $usd, array $ocr, array $extra = []): Payment
    {
        $this->seed();
        $producto = Product::firstOrFail();
        $cliente = User::where('email', '!=', 'admin@swworld.com')->firstOrFail();
        $order = app(\App\Services\ServicioPedidos::class)->crearPedidoDesdeLineas(
            [['producto_id' => $producto->id, 'cantidad' => 1, 'precio' => $usd, 'tipo' => 'detal']],
            $cliente->id, $cliente->id, 'online', null, ['tipo_entrega' => 'retiro', 'entrega_nombre' => 'A B', 'entrega_telefono' => '0414']
        );

        return Payment::create(array_merge([
            'pedido_id' => $order->id,
            'metodo' => 'pagomovil',
            'monto' => $usd,
            'moneda' => 'USD',
            'estado' => 'pendiente',
            'metadata_comprobante' => ['ocr' => array_merge(['disponible' => true, 'monto_bs' => null, 'fecha' => null, 'operacion' => null], $ocr)],
        ], $extra));
    }

    #[Test]
    public function el_monto_en_bs_se_valida_con_la_tasa_bcv_del_dia_del_pago()
    {
        ServicioPrecios::registrarTasaBcv(50.00, now()->subDays(2));
        ServicioPrecios::registrarTasaBcv(52.00, now());

        // El pago se hizo hoy (tasa 52): 20 USD = 1.040,00 Bs
        $pago = $this->pagoBs(20.00, ['monto_bs' => 1040.00, 'fecha' => now()->toDateString()]);
        $coh = app(ServicioValidacionPagos::class)->validarCoherencia($pago);

        $this->assertSame('VES', $coh['moneda_comparada']);
        $this->assertEquals(52.00, $coh['tasa_bcv']);
        $this->assertEquals(1040.00, $coh['monto_bs_esperado']);
        $this->assertTrue($coh['monto_coincide']);
        $this->assertSame('low', $coh['nivel_riesgo_coherencia']);
    }

    #[Test]
    public function un_monto_en_bs_que_no_cuadra_con_la_tasa_es_riesgo_alto()
    {
        ServicioPrecios::registrarTasaBcv(52.00, now());

        // Pagó al cambio de otra tasa (45): 900 Bs en vez de 1.040 Bs
        $pago = $this->pagoBs(20.00, ['monto_bs' => 900.00, 'fecha' => now()->toDateString()]);
        $coh = app(ServicioValidacionPagos::class)->validarCoherencia($pago);

        $this->assertFalse($coh['monto_coincide']);
        $this->assertSame('high', $coh['nivel_riesgo_coherencia']);
    }

    #[Test]
    public function lo_declarado_distinto_a_lo_leido_en_el_comprobante_es_alerta()
    {
        ServicioPrecios::registrarTasaBcv(52.00, now());

        $pago = $this->pagoBs(20.00, ['monto_bs' => 1040.00, 'fecha' => now()->toDateString(), 'operacion' => '001122334455'], [
            'numero_referencia' => '9999', // no son los últimos dígitos de la operación
        ]);
        $coh = app(ServicioValidacionPagos::class)->validarCoherencia($pago);

        $this->assertFalse($coh['ocr_coincide']);
        $this->assertContains('referencia', $coh['ocr_diferencias']);
        $this->assertSame('medium', $coh['nivel_riesgo_coherencia']);

        // Escribir solo los últimos dígitos sí es válido
        $pago->numero_referencia = '4455';
        $this->assertTrue(app(ServicioValidacionPagos::class)->validarCoherencia($pago)['ocr_coincide']);
    }

    #[Test]
    public function una_fecha_de_pago_del_mismo_dia_es_valida_aunque_no_tenga_hora()
    {
        ServicioPrecios::registrarTasaBcv(52.00, now());
        $pago = $this->pagoBs(20.00, ['monto_bs' => 1040.00, 'fecha' => now()->toDateString()]);

        $this->assertTrue(app(ServicioValidacionPagos::class)->validarCoherencia($pago)['fecha_valida']);
    }

    #[Test]
    public function el_checkout_lee_el_comprobante_y_autocompleta_los_datos()
    {
        // Mismo binario que usa el sistema (TESSERACT_PATH), no el del PATH
        $binario = config('pagos.ocr.tesseract', 'tesseract');
        try {
            $instalado = Process::run([$binario, '--version'])->successful();
        } catch (\Throwable $e) {
            $instalado = false;
        }
        if (! $instalado) {
            $this->markTestSkipped("Tesseract no está instalado en {$binario} (revisa TESSERACT_PATH en el .env).");
        }
        config(['pagos.ocr.habilitado' => true]);
        $this->seed();
        $this->configurarDatosDePago();
        ServicioPrecios::registrarTasaBcv(50.00, now());

        $cliente = User::where('email', '!=', 'admin@swworld.com')->firstOrFail();
        $producto = Product::where('activo', true)->where('stock', '>', 0)->firstOrFail();
        $totalBs = number_format($producto->precio_detal * 50, 2, ',', '.');

        $this->actingAs($cliente)->post(route('web.checkout.store'), [
            'items' => json_encode([['id' => $producto->id, 'cantidad' => 1, 'tipo' => 'detal']]),
            'tipo_entrega' => 'retiro',
            'entrega_nombre' => 'Cliente',
            'entrega_apellido' => 'Prueba',
            'entrega_telefono' => '04141234567',
            'metodo_pago' => 'pagomovil',
            'comprobante' => $this->comprobanteBdv($totalBs, now()->format('d/m/Y'), '001122334455'),
        ])->assertRedirect();

        $pago = Payment::latest('id')->firstOrFail();
        // Si falla, el mensaje muestra lo que leyó Tesseract en este equipo
        $leido = 'Texto OCR: ' . json_encode($pago->metadata_comprobante['ocr']['texto'] ?? $pago->metadata_comprobante['ocr'] ?? null, JSON_UNESCAPED_UNICODE);
        $this->assertSame('001122334455', $pago->numero_referencia, $leido);
        $this->assertEquals($producto->precio_detal * 50, (float) $pago->monto_declarado, $leido);
        $this->assertSame(now()->toDateString(), $pago->fecha_pago_declarada->toDateString());
        $this->assertContains('numero_referencia', $pago->metadata_comprobante['autocompletado']);
        $this->assertTrue($pago->metadata_comprobante['coherencia']['monto_coincide'], $leido);
        $this->assertEquals(50.00, (float) $pago->tasa_bcv_aplicada);
    }

    /** Comprobante sintético con el mismo esquema del BDV (texto claro sobre fondo oscuro). */
    private function comprobanteBdv(string $montoBs, string $fecha, string $operacion): UploadedFile
    {
        $lineas = ['PagomovilBDV Personas', '', "{$montoBs} Bs", '', "Fecha: {$fecha}", "Operacion: {$operacion}",
            'Identificacion: 12345678', 'Banco: 0102 - BANCO DE VENEZUELA', 'Concepto: prueba'];
        $w = 300;
        $h = 40 + count($lineas) * 20;
        $img = imagecreatetruecolor($w, $h);
        imagefill($img, 0, 0, imagecolorallocate($img, 34, 34, 34));
        $blanco = imagecolorallocate($img, 255, 255, 255);
        foreach ($lineas as $i => $l) {
            imagestring($img, 5, 20, 20 + $i * 20, $l, $blanco);
        }
        $big = imagescale($img, $w * 4, $h * 4, IMG_NEAREST_NEIGHBOUR);
        $ruta = tempnam(sys_get_temp_dir(), 'bdv') . '.png';
        imagepng($big, $ruta);

        return new UploadedFile($ruta, 'comprobante.png', 'image/png', null, true);
    }

    #[Test]
    public function detecta_si_el_pago_movil_no_fue_al_telefono_de_la_tienda()
    {
        ServicioPrecios::registrarTasaBcv(52.00, now());
        $pago = $this->pagoBs(20.00, ['monto_bs' => 1040.00, 'fecha' => now()->toDateString(), 'destino' => '04140000000']);
        $this->configurarDatosDePago(); // teléfono de la tienda: 0414-0000000

        $this->assertTrue(app(ServicioValidacionPagos::class)->validarCoherencia($pago)['destino_coincide']);

        // Mismo pago, pero hecho a otro teléfono: alerta (riesgo medio)
        $pago->metadata_comprobante = ['ocr' => ['disponible' => true, 'monto_bs' => 1040.00, 'fecha' => now()->toDateString(), 'operacion' => null, 'destino' => '04241112233']];
        $coh = app(ServicioValidacionPagos::class)->validarCoherencia($pago);
        $this->assertFalse($coh['destino_coincide']);
        $this->assertSame('medium', $coh['nivel_riesgo_coherencia']);
    }

    #[Test]
    public function la_cuenta_enmascarada_de_una_transferencia_se_compara_por_sus_extremos()
    {
        ServicioPrecios::registrarTasaBcv(52.00, now());
        $pago = $this->pagoBs(20.00, ['monto_bs' => 1040.00, 'destino' => '0102****0000'], ['metodo' => 'transferencia']);
        $this->configurarDatosDePago(); // cuenta 0102 0000 00 0000000000

        $this->assertTrue(app(ServicioValidacionPagos::class)->validarCoherencia($pago)['destino_coincide']);

        // Una lectura OCR deformada no se usa para acusar: queda sin decidir
        $pago->metadata_comprobante = ['ocr' => ['disponible' => true, 'monto_bs' => 1040.00, 'fecha' => null, 'operacion' => null, 'destino' => '010721485']];
        $this->assertNull(app(ServicioValidacionPagos::class)->validarCoherencia($pago)['destino_coincide']);
    }

    #[Test]
    public function el_ocr_extrae_el_destino_del_comprobante()
    {
        $this->assertSame('04140000000', ServicioOcrComprobantes::extraerCampos(self::TEXTO_PAGO_MOVIL)['destino']);
        $this->assertSame('0102****2222', ServicioOcrComprobantes::extraerCampos(self::TEXTO_TRANSFERENCIA)['destino']);
    }
}

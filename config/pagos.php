<?php

return [
    // Tolerancia absoluta en la comparación de montos (misma moneda), en unidades monetarias.
    // Valor por defecto 0.50 (cincuenta centavos) para cubrir redondeos y pequeñas discrepancias.
    'tolerancia_monto' => env('PAGOS_TOLERANCIA_MONTO', 0.50),

    // Formato de fecha permitido para la fecha de pago declarada en los formularios
    'fecha_format' => 'Y-m-d',

    // Pagos en bolívares: diferencia máxima (en %) entre el monto del comprobante
    // y total USD × tasa BCV del día del pago. Cubre redondeos y el cambio de tasa
    // entre la publicación del BCV y la hora del pago.
    'tolerancia_bs_porcentaje' => env('PAGOS_TOLERANCIA_BS_PCT', 1.0),

    // Días antes de crear el pedido en los que se acepta la fecha del pago
    // (el cliente a veces paga y luego hace el pedido).
    'dias_pago_antes_pedido' => env('PAGOS_DIAS_ANTES_PEDIDO', 1),

    // OCR de comprobantes con Tesseract. En Windows/Laragon, TESSERACT_PATH suele ser
    // "C:\Program Files\Tesseract-OCR\tesseract.exe".
    'ocr' => [
        'habilitado' => env('OCR_HABILITADO', true),
        'tesseract' => env('TESSERACT_PATH', 'tesseract'),
        'idioma' => env('TESSERACT_IDIOMA', 'spa'),
    ],
];

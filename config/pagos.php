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

    // Bancos venezolanos (código SUDEBAN) para el campo "banco de origen".
    'bancos' => [
        '0102' => 'Banco de Venezuela',
        '0104' => 'Venezolano de Crédito',
        '0105' => 'Mercantil',
        '0108' => 'Provincial',
        '0114' => 'Bancaribe',
        '0115' => 'Exterior',
        '0128' => 'Banco Caroní',
        '0134' => 'Banesco',
        '0137' => 'Sofitasa',
        '0138' => 'Banco Plaza',
        '0151' => 'BFC Banco Fondo Común',
        '0156' => '100% Banco',
        '0157' => 'DelSur',
        '0163' => 'Banco del Tesoro',
        '0166' => 'Banco Agrícola de Venezuela',
        '0168' => 'Bancrecer',
        '0169' => 'Mi Banco',
        '0171' => 'Banco Activo',
        '0172' => 'Bancamiga',
        '0174' => 'Banplus',
        '0175' => 'Banco Bicentenario',
        '0177' => 'Banfanb',
        '0191' => 'BNC Banco Nacional de Crédito',
    ],

    // OCR de comprobantes con Tesseract. En Windows/Laragon, TESSERACT_PATH suele ser
    // "C:\Program Files\Tesseract-OCR\tesseract.exe".
    'ocr' => [
        'habilitado' => env('OCR_HABILITADO', true),
        'tesseract' => env('TESSERACT_PATH', 'tesseract'),
        'idioma' => env('TESSERACT_IDIOMA', 'spa'),
    ],
];

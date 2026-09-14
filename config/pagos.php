<?php

return [
    // Tolerancia absoluta en la comparación de montos (misma moneda), en unidades monetarias.
    // Valor por defecto 0.50 (cincuenta centavos) para cubrir redondeos y pequeñas discrepancias.
    'tolerancia_monto' => env('PAGOS_TOLERANCIA_MONTO', 0.50),

    // Formato de fecha permitido para la fecha de pago declarada en los formularios
    'fecha_format' => 'Y-m-d',
];

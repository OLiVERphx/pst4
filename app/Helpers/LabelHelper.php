<?php

namespace App\Helpers;

/**
 * Helper estático para traducir estados y etiquetas a textos legibles en español.
 * Un archivo = una responsabilidad.
 */
class LabelHelper
{
    /**
     * Traduce valores de estado (orders/payments) a etiquetas legibles en español.
     *
     * @param string|null $status
     * @return string
     */
    public static function translateStatus(?string $status): string
    {
        $s = (string) $status;
        $map = [
            // Pedidos
            'pending' => 'Pendiente',
            'pendiente' => 'Pendiente',
            'pago_subido' => 'Pago subido',
            'pago_verificado' => 'Pago verificado',
            'procesando' => 'Procesando',
            'enviado' => 'Enviado',
            'entregado' => 'Entregado',
            'cancelado' => 'Cancelado',
            'activo' => 'Activo',
            'inactivo' => 'Inactivo',

            // Pagos
            'valido' => 'Válido',
            'invalido' => 'Inválido',
            'sospechoso' => 'Sospechoso',
        ];

        return $map[$s] ?? ucfirst(str_replace('_', ' ', $s));
    }
}

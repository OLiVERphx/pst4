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
            'listo_para_retirar' => 'Listo para retirar',
            'pago_subido' => 'Pago subido',
            'pago_a_confirmar' => 'Pago a confirmar',
            'pago_confirmado_retirar' => 'Pago confirmado — puede retirar',
            'pago_confirmado' => 'Pago confirmado',
            'en_camino' => 'En camino',
            'entregado' => 'Entregado',
            'completado' => 'Completado',
            'cancelado' => 'Cancelado',
            'pago_verificado' => 'Pago verificado',
            'procesando' => 'Procesando',
            'enviado' => 'Enviado',
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

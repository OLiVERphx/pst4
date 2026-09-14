<?php

namespace App\Services;

use App\Models\Cart;
use Carbon\Carbon;

/**
 * Clase invocable para liberar reservas expiradas. Puede ser llamada desde el scheduler
 * por ejemplo: \App\Services\ReleaseExpiredReservationsJob::handle()
 */
class ReleaseExpiredReservationsJob
{
    public function handle()
    {
        $now = Carbon::now();
        $expired = Cart::where('estado', 'reserved')
            ->whereNotNull('reserved_at')
            ->where('reserved_at', '<=', $now)
            ->get();

        $service = app(ReservationService::class);
        foreach ($expired as $cart) {
            try {
                $service->releaseCartReservations($cart);
            } catch (\Throwable $e) {
                logger()->error('ReleaseExpiredReservationsJob failed for cart ' . $cart->id . ': ' . $e->getMessage());
            }
        }
    }
}

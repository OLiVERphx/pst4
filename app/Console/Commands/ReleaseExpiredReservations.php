<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReleaseExpiredReservations extends Command
{
    protected $signature = 'reservations:release-expired';
    protected $description = 'Libera reservas de stock de carritos expirados';

    public function handle()
    {
        app(\App\Services\ReleaseExpiredReservationsJob::class)->handle();
    }
}

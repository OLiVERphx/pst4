<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            // Monto declarado por el cliente al subir el comprobante
            $table->decimal('monto_declarado', 10, 2)->nullable()->after('monto')->comment('Monto declarado por el cliente al subir comprobante');
            // Moneda declarada por el cliente (si aplica)
            $table->string('moneda_declarada', 5)->nullable()->after('monto_declarado')->comment('Moneda declarada por el cliente');
            // Fecha del pago declarada por el cliente
            $table->dateTime('fecha_pago_declarada')->nullable()->after('moneda_declarada')->comment('Fecha reportada del pago por el cliente');

            $table->index('monto_declarado');
            $table->index('fecha_pago_declarada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropIndex(['monto_declarado']);
            $table->dropIndex(['fecha_pago_declarada']);
            $table->dropColumn(['monto_declarado', 'moneda_declarada', 'fecha_pago_declarada']);
        });
    }
};

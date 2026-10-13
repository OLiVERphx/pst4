<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->enum('tipo_entrega', ['retiro', 'delivery'])->default('retiro')->after('canal');
            $table->dateTime('fecha_estimada_entrega')->nullable()->after('tipo_entrega');

            $table->enum('estado', [
                'pending','valid','pendiente',
                'listo_para_retirar',
                'pago_subido','pago_a_confirmar',
                'pago_confirmado_retirar','pago_confirmado',
                'en_camino','entregado','completado',
                'pago_verificado','procesando','enviado',
                'cancelado',
            ])->default('pendiente')->change();
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn(['tipo_entrega', 'fecha_estimada_entrega']);

            $table->enum('estado', [
                'pending','valid','pendiente','pago_subido','pago_verificado',
                'procesando','enviado','entregado','cancelado',
            ])->default('pendiente')->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            // 1. Ampliar ENUMs temporalmente para aceptar valores en inglés y en español
            Schema::table('pedidos', function (Blueprint $table) {
                DB::statement("ALTER TABLE pedidos MODIFY tipo ENUM('retail','wholesale','detal','mayor') DEFAULT 'detal'");
                DB::statement("ALTER TABLE pedidos MODIFY estado ENUM('pending','payment_uploaded','payment_verified','processing','shipped','delivered','cancelled','pendiente','pago_subido','pago_verificado','procesando','enviado','entregado','cancelado') DEFAULT 'pendiente'");
            });

            Schema::table('pagos', function (Blueprint $table) {
                DB::statement("ALTER TABLE pagos MODIFY estado ENUM('pending','valid','invalid','suspicious','pendiente','valido','invalido','sospechoso') DEFAULT 'pendiente'");
            });
        }

        // 2. Normalizar registros existentes de inglés a español
        DB::table('pedidos')->where('tipo', 'retail')->update(['tipo' => 'detal']);
        DB::table('pedidos')->where('tipo', 'wholesale')->update(['tipo' => 'mayor']);
        DB::table('pedidos')->where('estado', 'pending')->update(['estado' => 'pendiente']);
        DB::table('pedidos')->where('estado', 'payment_uploaded')->update(['estado' => 'pago_subido']);
        DB::table('pedidos')->where('estado', 'payment_verified')->update(['estado' => 'pago_verificado']);
        DB::table('pedidos')->where('estado', 'processing')->update(['estado' => 'procesando']);
        DB::table('pedidos')->where('estado', 'shipped')->update(['estado' => 'enviado']);
        DB::table('pedidos')->where('estado', 'delivered')->update(['estado' => 'entregado']);
        DB::table('pedidos')->where('estado', 'cancelled')->update(['estado' => 'cancelado']);

        DB::table('pagos')->where('estado', 'pending')->update(['estado' => 'pendiente']);
        DB::table('pagos')->where('estado', 'valid')->update(['estado' => 'valido']);
        DB::table('pagos')->where('estado', 'invalid')->update(['estado' => 'invalido']);
        DB::table('pagos')->where('estado', 'suspicious')->update(['estado' => 'sospechoso']);

        if ($driver === 'mysql') {
            // 3. Restringir ENUMs únicamente a los valores en español
            Schema::table('pedidos', function (Blueprint $table) {
                DB::statement("ALTER TABLE pedidos MODIFY tipo ENUM('detal','mayor') DEFAULT 'detal'");
                DB::statement("ALTER TABLE pedidos MODIFY estado ENUM('pendiente','pago_subido','pago_verificado','procesando','enviado','entregado','cancelado') DEFAULT 'pendiente'");
            });

            Schema::table('pagos', function (Blueprint $table) {
                DB::statement("ALTER TABLE pagos MODIFY estado ENUM('pendiente','valido','invalido','sospechoso') DEFAULT 'pendiente'");
            });
        }
    }

    public function down()
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            Schema::table('pedidos', function (Blueprint $table) {
                DB::statement("ALTER TABLE pedidos MODIFY tipo ENUM('retail','wholesale','detal','mayor') DEFAULT 'retail'");
                DB::statement("ALTER TABLE pedidos MODIFY estado ENUM('pending','payment_uploaded','payment_verified','processing','shipped','delivered','cancelled','pendiente','pago_subido','pago_verificado','procesando','enviado','entregado','cancelado') DEFAULT 'pending'");
            });

            Schema::table('pagos', function (Blueprint $table) {
                DB::statement("ALTER TABLE pagos MODIFY estado ENUM('pending','valid','invalid','suspicious','pendiente','valido','invalido','sospechoso') DEFAULT 'pending'");
            });
        }

        DB::table('pedidos')->where('tipo', 'detal')->update(['tipo' => 'retail']);
        DB::table('pedidos')->where('tipo', 'mayor')->update(['tipo' => 'wholesale']);
        DB::table('pedidos')->where('estado', 'pendiente')->update(['estado' => 'pending']);
        DB::table('pedidos')->where('estado', 'pago_subido')->update(['estado' => 'payment_uploaded']);
        DB::table('pedidos')->where('estado', 'pago_verificado')->update(['estado' => 'payment_verified']);
        DB::table('pedidos')->where('estado', 'procesando')->update(['estado' => 'processing']);
        DB::table('pedidos')->where('estado', 'enviado')->update(['estado' => 'shipped']);
        DB::table('pedidos')->where('estado', 'entregado')->update(['estado' => 'delivered']);
        DB::table('pedidos')->where('estado', 'cancelado')->update(['estado' => 'cancelled']);

        DB::table('pagos')->where('estado', 'pendiente')->update(['estado' => 'pending']);
        DB::table('pagos')->where('estado', 'valido')->update(['estado' => 'valid']);
        DB::table('pagos')->where('estado', 'invalido')->update(['estado' => 'invalid']);
        DB::table('pagos')->where('estado', 'sospechoso')->update(['estado' => 'suspicious']);

        if ($driver === 'mysql') {
            Schema::table('pedidos', function (Blueprint $table) {
                DB::statement("ALTER TABLE pedidos MODIFY tipo ENUM('retail','wholesale') DEFAULT 'retail'");
                DB::statement("ALTER TABLE pedidos MODIFY estado ENUM('pending','payment_uploaded','payment_verified','processing','shipped','delivered','cancelled') DEFAULT 'pending'");
            });

            Schema::table('pagos', function (Blueprint $table) {
                DB::statement("ALTER TABLE pagos MODIFY estado ENUM('pending','valid','invalid','suspicious') DEFAULT 'pending'");
            });
        }
    }
};

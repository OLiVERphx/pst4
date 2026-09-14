<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeederProductos extends Seeder
{
    public function run(): void
    {
        $now = now();

        $brands = DB::table('brands')->pluck('id', 'nombre')->toArray();
        $categories = DB::table('categories')->pluck('id', 'nombre')->toArray();

        $products = [
            ['codigo' => 'ADC-1', 'nombre' => 'Audífonos Infinix', 'categoria' => 'Audífonos', 'marca' => 'Infinix', 'precio_detal' => '2.00', 'precio_mayor' => '1.60', 'precio_costo' => '0.60', 'stock' => 21, 'stock_minimo' => 3],
            ['codigo' => 'ADC-2', 'nombre' => 'Audífonos Xiaomi', 'categoria' => 'Audífonos', 'marca' => 'Xiaomi', 'precio_detal' => '2.00', 'precio_mayor' => '1.60', 'precio_costo' => '0.60', 'stock' => 15, 'stock_minimo' => 3],
            ['codigo' => 'ADC-3', 'nombre' => 'Audífonos Hi-Treek HT-204', 'categoria' => 'Audífonos', 'marca' => 'Hi-Treek', 'precio_detal' => '5.00', 'precio_mayor' => '4.00', 'precio_costo' => '2.50', 'stock' => 5, 'stock_minimo' => 3],
            ['codigo' => 'ADI-1', 'nombre' => 'Audífonos Hyundai', 'categoria' => 'Audífonos', 'marca' => 'Hyundai', 'precio_detal' => '25.00', 'precio_mayor' => '20.00', 'precio_costo' => '15.00', 'stock' => 1, 'stock_minimo' => 3],
            ['codigo' => 'FUN-1', 'nombre' => 'Funda Samsung A14', 'categoria' => 'Fundas', 'marca' => 'Samsung', 'precio_detal' => '3.50', 'precio_mayor' => '2.80', 'precio_costo' => '1.20', 'stock' => 45, 'stock_minimo' => 5],
            ['codigo' => 'FUN-2', 'nombre' => 'Funda iPhone 15', 'categoria' => 'Fundas', 'marca' => 'Apple', 'precio_detal' => '5.00', 'precio_mayor' => '4.00', 'precio_costo' => '2.00', 'stock' => 30, 'stock_minimo' => 5],
            ['codigo' => 'FUN-3', 'nombre' => 'Funda Xiaomi Redmi 12', 'categoria' => 'Fundas', 'marca' => 'Xiaomi', 'precio_detal' => '3.00', 'precio_mayor' => '2.40', 'precio_costo' => '1.00', 'stock' => 28, 'stock_minimo' => 5],
            ['codigo' => 'CAR-1', 'nombre' => 'Cargador USB-C 33W', 'categoria' => 'Cargadores', 'marca' => 'Anker', 'precio_detal' => '8.00', 'precio_mayor' => '6.50', 'precio_costo' => '3.50', 'stock' => 60, 'stock_minimo' => 10],
            ['codigo' => 'CAR-2', 'nombre' => 'Cargador Inalámbrico 15W', 'categoria' => 'Cargadores', 'marca' => 'Baseus', 'precio_detal' => '12.00', 'precio_mayor' => '9.50', 'precio_costo' => '5.00', 'stock' => 18, 'stock_minimo' => 5],
            ['codigo' => 'CAB-1', 'nombre' => 'Cable USB-C 2m Trenzado', 'categoria' => 'Cables', 'marca' => 'Baseus', 'precio_detal' => '4.00', 'precio_mayor' => '3.20', 'precio_costo' => '1.50', 'stock' => 85, 'stock_minimo' => 10],
            ['codigo' => 'CAB-2', 'nombre' => 'Cable Lightning 1m', 'categoria' => 'Cables', 'marca' => 'Apple', 'precio_detal' => '6.00', 'precio_mayor' => '4.80', 'precio_costo' => '2.50', 'stock' => 22, 'stock_minimo' => 5],
            ['codigo' => 'PRO-1', 'nombre' => 'Protector Samsung A54', 'categoria' => 'Protectores', 'marca' => 'Samsung', 'precio_detal' => '2.50', 'precio_mayor' => '1.80', 'precio_costo' => '0.80', 'stock' => 70, 'stock_minimo' => 10],
            ['codigo' => 'PRO-2', 'nombre' => 'Protector 3D iPhone 14', 'categoria' => 'Protectores', 'marca' => 'Apple', 'precio_detal' => '4.00', 'precio_mayor' => '3.20', 'precio_costo' => '1.50', 'stock' => 35, 'stock_minimo' => 5],
            ['codigo' => 'BAT-1', 'nombre' => 'Batería Portátil 10000mAh', 'categoria' => 'Baterías', 'marca' => 'Xiaomi', 'precio_detal' => '18.00', 'precio_mayor' => '14.00', 'precio_costo' => '8.00', 'stock' => 12, 'stock_minimo' => 3],
            ['codigo' => 'SOP-1', 'nombre' => 'Soporte Auto Magnético', 'categoria' => 'Soportes', 'marca' => 'Baseus', 'precio_detal' => '7.00', 'precio_mayor' => '5.50', 'precio_costo' => '3.00', 'stock' => 40, 'stock_minimo' => 5],
            ['codigo' => 'SOP-2', 'nombre' => 'Soporte Escritorio Ajustable', 'categoria' => 'Soportes', 'marca' => 'Anker', 'precio_detal' => '9.00', 'precio_mayor' => '7.20', 'precio_costo' => '4.00', 'stock' => 20, 'stock_minimo' => 3],
        ];

        $rows = [];
        foreach ($products as $product) {
            $rows[] = [
                'codigo' => $product['codigo'],
                'nombre' => $product['nombre'],
                'slug' => Str::slug($product['nombre']),
                'descripcion' => null,
                'marca_id' => $brands[$product['marca']] ?? null,
                'categoria_id' => $categories[$product['categoria']] ?? null,
                'precio_detal' => $product['precio_detal'],
                'precio_mayor' => $product['precio_mayor'],
                'precio_costo' => $product['precio_costo'],
                'min_cantidad_mayor' => 10,
                'stock' => $product['stock'],
                'stock_minimo' => $product['stock_minimo'],
                'imagenes' => null,
                'activo' => true,
                'destacado' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('products')->insertOrIgnore($rows);
    }
}

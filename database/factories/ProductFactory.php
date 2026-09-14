<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $brandId = Brand::query()->value('id')
            ?? Brand::create(['nombre' => $this->faker->unique()->company()])->id;
        $categoryId = Category::query()->value('id')
            ?? Category::create([
                'nombre' => $this->faker->unique()->word(),
                'slug' => $this->faker->unique()->slug(),
                'activo' => true,
            ])->id;

        return [
            'codigo' => $this->faker->unique()->bothify('???###'),
            'nombre' => $this->faker->words(3, true),
            'slug' => $this->faker->unique()->slug(),
            'descripcion' => $this->faker->sentence(),
            'marca_id' => $brandId,
            'categoria_id' => $categoryId,
            'precio_detal' => $this->faker->randomFloat(2, 10, 1000),
            'precio_mayor' => $this->faker->randomFloat(2, 5, 900),
            'precio_costo' => $this->faker->randomFloat(2, 1, 800),
            'min_cantidad_mayor' => $this->faker->numberBetween(1, 50),
            'stock' => $this->faker->numberBetween(0, 100),
            'stock_minimo' => $this->faker->numberBetween(0, 20),
            'imagenes' => [$this->faker->imageUrl()],
            'activo' => $this->faker->boolean(),
            'destacado' => $this->faker->boolean(),
        ];
    }
}

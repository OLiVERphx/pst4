<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Services\ServicioBusquedaInteligente;

class BusquedaTfidfTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Caso de éxito: una búsqueda con sinónimo/tipeo leve debe encontrar el producto relevante.
     */
    public function test_search_fuzzy_returns_relevant_product()
    {
        // Crear productos de prueba
        // Crear marca y categoría mínimas necesarias
        $brandId =         \DB::table('marcas')->insertGetId(['nombre'=>'MarcaTest','created_at'=>now(),'updated_at'=>now()]);
        $catId = \DB::table('categorias')->insertGetId(['nombre'=>'Cargadores','slug'=>'cargadores','created_at'=>now(),'updated_at'=>now()]);

        $p1 = Product::create(["codigo"=>"C001","nombre"=>"Cargador de carga rápida 20W","descripcion"=>"Cargador USB-C 20W carga rápida","marca_id"=>$brandId,"categoria_id"=>$catId,"precio_detal"=>10.0,"precio_mayor"=>8.0,"precio_costo"=>6.0,"stock"=>10,"stock_minimo"=>2,"activo"=>true]);
        $p2 = Product::create(["codigo"=>"A002","nombre"=>"Audífonos Bluetooth","descripcion"=>"Audífonos in-ear con micrófono","marca_id"=>$brandId,"categoria_id"=>$catId,"precio_detal"=>20.0,"precio_mayor"=>15.0,"precio_costo"=>10.0,"stock"=>5,"stock_minimo"=>1,"activo"=>true]);

        $svc = new ServicioBusquedaInteligente();
        $svc->buildIndex();

        // Búsqueda con tipeo leve y sinónimo
        $ids = $svc->search('cargador rapido', 10);

        $this->assertContains($p1->id, $ids, 'La búsqueda TF-IDF no devolvió el cargador esperado para "cargador rapido"');
    }

    /**
     * Un producto sin ventas ni búsquedas previas puede aparecer recomendado por stock alto.
     */
    public function test_high_stock_product_without_sales_can_be_recommended()
    {
        $brandId =         \DB::table('marcas')->insertGetId(['nombre'=>'MarcaTest2','created_at'=>now(),'updated_at'=>now()]);
        $catId = \DB::table('categorias')->insertGetId(['nombre'=>'CategoriaTest','slug'=>'categoria-test','created_at'=>now(),'updated_at'=>now()]);

        $p1 = Product::create(["codigo"=>"P001","nombre"=>"Producto A","descripcion"=>"Descripcion A","marca_id"=>$brandId,"categoria_id"=>$catId,"precio_detal"=>5.0,"precio_mayor"=>4.0,"precio_costo"=>3.0,"stock"=>100,"stock_minimo"=>1,"activo"=>true]);
        $p2 = Product::create(["codigo"=>"P002","nombre"=>"Producto B","descripcion"=>"Descripcion B","marca_id"=>$brandId,"categoria_id"=>$catId,"precio_detal"=>6.0,"precio_mayor"=>5.0,"precio_costo"=>4.0,"stock"=>2,"stock_minimo"=>1,"activo"=>true]);

        $svc = new ServicioBusquedaInteligente();
        $svc->buildIndex();

        $related = $svc->relatedProducts($p2->id, ['tfidf'=>0.0,'buscado'=>0.0,'vendido'=>0.0,'stock'=>1.0], 5);

        // El producto con stock 100 debe ser recomendado por mayor stock
        $this->assertContains($p1->id, $related);
    }
}

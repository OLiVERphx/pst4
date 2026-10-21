<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Services\Importacion\ServicioImportacionProductos;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProductController extends Controller
{
    public function index()
    {
        return view('admin.products.index');
    }

    public function importar()
    {
        return view('admin.products.importar');
    }

    /**
     * Plantilla de importación: los encabezados que el importador reconoce sin ajustes,
     * dos filas de ejemplo y las marcas y categorías ya registradas.
     */
    public function plantillaImportacion()
    {
        $libro = new Spreadsheet();
        $hoja = $libro->getActiveSheet()->setTitle('Productos');

        $encabezados = array_column(ServicioImportacionProductos::CAMPOS, 'etiqueta');
        $hoja->fromArray([
            $encabezados,
            ['SM-A15-128', 'Samsung Galaxy A15 128GB', 'Pantalla 6.5", 4GB RAM', 'Samsung', 'Teléfonos', 180, 165, 140, 10, 3, 5, 'Sí'],
            ['FUN-IP15-TR', 'Funda transparente iPhone 15', null, 'Genérica', 'Accesorios', 5, 3.5, 1.2, 50, 10, 12, 'Sí'],
        ]);
        $ultima = $hoja->getHighestColumn();
        $hoja->getStyle("A1:{$ultima}1")->getFont()->setBold(true);
        $hoja->getStyle("A1:{$ultima}1")->getFill()->setFillType('solid')->getStartColor()->setRGB('DBEAFE');
        $hoja->freezePane('A2');
        foreach (range('A', $ultima) as $columna) {
            $hoja->getColumnDimension($columna)->setAutoSize(true);
        }

        $instrucciones = $libro->createSheet()->setTitle('Instrucciones');
        $instrucciones->fromArray(array_map(fn ($t) => [$t], [
            'Cómo llenar la plantilla',
            'Una fila por producto. No combine celdas ni deje filas de títulos entre los productos.',
            'Obligatorios para crear: Código, Nombre, Marca, Categoría y Precio detal.',
            'Para actualizar basta el Código y las columnas que cambian: las celdas vacías no borran datos.',
            'Precios en dólares (o elija "bolívares" al importar y se convierten con la tasa BCV del día).',
            'Stock: existencia actual. El sistema registra la diferencia como movimiento de inventario.',
            'Marcas y categorías que no existan se crean al importar (se puede desactivar).',
            'Activo: Sí o No.',
            'Puede usar su propio Excel: al importarlo se indica qué columna corresponde a cada dato.',
        ]));
        $instrucciones->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $instrucciones->getColumnDimension('A')->setWidth(100);

        $listas = $libro->createSheet()->setTitle('Marcas y categorías');
        $listas->fromArray([['Marcas registradas', 'Categorías registradas']]);
        $listas->fromArray(array_map(fn ($m) => [$m], Brand::orderBy('nombre')->pluck('nombre')->all()), null, 'A2');
        $listas->fromArray(array_map(fn ($c) => [$c], Category::orderBy('nombre')->pluck('nombre')->all()), null, 'B2');
        $listas->getStyle('A1:B1')->getFont()->setBold(true);
        $listas->getColumnDimension('A')->setAutoSize(true);
        $listas->getColumnDimension('B')->setAutoSize(true);

        $libro->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($libro) {
            (new Xlsx($libro))->save('php://output');
            $libro->disconnectWorksheets();
        }, 'plantilla_productos.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}

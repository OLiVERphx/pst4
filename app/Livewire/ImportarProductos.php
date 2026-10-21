<?php

namespace App\Livewire;

use App\Services\Importacion\LectorHojaCalculo;
use App\Services\Importacion\ServicioImportacionProductos;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Asistente de importación de productos desde Excel: subir → asignar columnas → vista previa → importar.
 * Del archivo solo se guarda la ruta; cada paso lo vuelve a leer y la importación repite el análisis
 * en el servidor, así lo que se guarda no depende de datos enviados por el navegador.
 */
class ImportarProductos extends Component
{
    use WithFileUploads;

    private const DISCO = 'local';
    private const CARPETA = 'importaciones';
    private const FILAS_VISTA_PREVIA = 100;

    public $archivo;

    #[Locked]
    public int $paso = 1;
    #[Locked]
    public ?string $ruta = null;
    #[Locked]
    public string $nombreArchivo = '';
    #[Locked]
    public array $hojas = [];
    #[Locked]
    public array $encabezados = [];
    #[Locked]
    public array $filasDisponibles = [];
    #[Locked]
    public array $analisis = [];
    #[Locked]
    public array $resultado = [];

    public int $hoja = 0;
    public ?int $filaEncabezados = null;
    public array $mapeo = [];
    public string $modo = ServicioImportacionProductos::MODO_CREAR_Y_ACTUALIZAR;
    public string $moneda = 'USD';
    public bool $crearFaltantes = true;

    public function mount(): void
    {
        Gate::authorize('productos.editar');
    }

    public function subir(): void
    {
        Gate::authorize('productos.editar');
        $this->resetErrorBag();

        $this->validate([
            'archivo' => 'required|file|max:5120|mimes:xlsx,xls,ods,csv,txt',
        ], [
            'archivo.mimes' => 'Solo se aceptan archivos de Excel (.xlsx, .xls), LibreOffice (.ods) o .csv.',
            'archivo.max' => 'El archivo no puede pesar más de 5 MB.',
        ]);

        $this->limpiarArchivosViejos();
        $this->borrarArchivo();

        $extension = Str::lower($this->archivo->getClientOriginalExtension()) ?: 'xlsx';
        $this->ruta = $this->archivo->storeAs(self::CARPETA, Str::uuid() . '.' . $extension, self::DISCO);
        $this->nombreArchivo = Str::limit($this->archivo->getClientOriginalName(), 120);
        $this->archivo = null;
        $this->hoja = 0;

        if ($this->cargarHoja(true)) {
            $this->paso = 2;
        }
    }

    public function updatedHoja(): void
    {
        $this->cargarHoja(true);
    }

    public function updatedFilaEncabezados(): void
    {
        $this->cargarHoja(false);
    }

    public function analizar(): void
    {
        Gate::authorize('productos.editar');
        $this->resetErrorBag();

        $analisis = $this->ejecutarAnalisis();
        if ($analisis === null) {
            return;
        }

        // En pantalla: primero las filas con error, luego el resto, hasta FILAS_VISTA_PREVIA
        $filas = collect($analisis['filas'])->sortBy(fn ($f) => [$f['accion'] === 'error' ? 0 : 1, $f['fila']])->values();
        $analisis['total_filas'] = $filas->count();
        $analisis['filas'] = $filas->take(self::FILAS_VISTA_PREVIA)->all();
        $this->analisis = $analisis;
        $this->paso = 3;
    }

    public function importar(): void
    {
        Gate::authorize('productos.editar');
        $this->resetErrorBag();

        $analisis = $this->ejecutarAnalisis();
        if ($analisis === null) {
            return;
        }

        try {
            $this->resultado = $this->servicio()->importar($analisis, Auth::user(), $this->nombreArchivo);
        } catch (\Throwable $e) {
            report($e);
            $this->addError('importacion', 'No se importó nada: ' . $e->getMessage());

            return;
        }

        $this->borrarArchivo();
        $this->analisis = [];
        $this->paso = 4;
    }

    /** Descarga las filas con error y el motivo, para corregirlas en el Excel y volver a subirlo. */
    public function descargarErrores()
    {
        Gate::authorize('productos.editar');
        $this->resetErrorBag();

        $analisis = $this->ejecutarAnalisis();
        if ($analisis === null) {
            return null;
        }

        $nombre = 'errores_' . pathinfo($this->nombreArchivo, PATHINFO_FILENAME) . '.csv';

        return response()->streamDownload(function () use ($analisis) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF"); // BOM: Excel abre el CSV con tildes correctas
            fputcsv($salida, ['Fila', 'Código', 'Nombre', 'Errores', 'Avisos'], ';');
            foreach ($analisis['filas'] as $fila) {
                if ($fila['accion'] === 'error') {
                    fputcsv($salida, [$fila['fila'], $fila['codigo'], $fila['nombre'], implode(' ', $fila['errores']), implode(' ', $fila['avisos'])], ';');
                }
            }
            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function volverAMapeo(): void
    {
        $this->analisis = [];
        $this->paso = $this->ruta ? 2 : 1;
    }

    public function reiniciar(): void
    {
        $this->borrarArchivo();
        $this->reset(['archivo', 'paso', 'nombreArchivo', 'hojas', 'encabezados', 'filasDisponibles',
            'analisis', 'resultado', 'hoja', 'filaEncabezados', 'mapeo']);
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.importar-productos', [
            'campos' => ServicioImportacionProductos::CAMPOS,
            'puedeAjustarStock' => Gate::allows('inventario.ajustar'),
        ]);
    }

    /**
     * Lee la hoja elegida y arma los encabezados. Con $detectar, además ubica la fila de
     * encabezados y propone el mapeo de columnas.
     */
    private function cargarHoja(bool $detectar): bool
    {
        $filas = $this->leer();
        if ($filas === null) {
            return false;
        }

        $this->filasDisponibles = array_slice(array_keys($filas), 0, 15);
        if ($detectar || !isset($filas[$this->filaEncabezados])) {
            $this->filaEncabezados = $this->servicio()->detectarFilaEncabezados($filas);
        }

        $celdas = $filas[$this->filaEncabezados] ?? [];
        $this->encabezados = [];
        foreach ($celdas as $columna => $valor) {
            $letra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columna + 1);
            $this->encabezados[$columna] = $letra . ($valor !== null ? ': ' . Str::limit((string) $valor, 40) : '');
        }

        $sugerido = $this->servicio()->sugerirMapeo($celdas);
        $this->mapeo = [];
        foreach (array_keys(ServicioImportacionProductos::CAMPOS) as $campo) {
            $this->mapeo[$campo] = isset($sugerido[$campo]) ? (string) $sugerido[$campo] : '';
        }
        if (!Gate::allows('inventario.ajustar')) {
            $this->mapeo['stock'] = '';
        }

        return true;
    }

    private function ejecutarAnalisis(): ?array
    {
        $this->validate([
            'modo' => 'required|in:crear_actualizar,solo_crear,solo_actualizar',
            'moneda' => 'required|in:USD,VES',
            'filaEncabezados' => 'required|integer|min:1',
            'mapeo.*' => 'nullable|integer|min:0|max:' . (LectorHojaCalculo::MAX_COLUMNAS - 1),
        ]);

        $filas = $this->leer();
        if ($filas === null) {
            return null;
        }

        $analisis = $this->servicio()->analizar($filas, (int) $this->filaEncabezados, $this->mapeo, [
            'modo' => $this->modo,
            'moneda' => $this->moneda,
            'crear_faltantes' => $this->crearFaltantes,
            'puede_ajustar_stock' => Gate::allows('inventario.ajustar'),
        ]);

        if ($analisis['errores_generales']) {
            foreach ($analisis['errores_generales'] as $i => $mensaje) {
                $this->addError('mapeo_' . $i, $mensaje);
            }
            $this->paso = 2;

            return null;
        }

        return $analisis;
    }

    private function leer(): ?array
    {
        if (!$this->ruta || !Storage::disk(self::DISCO)->exists($this->ruta)) {
            $this->addError('archivo', 'El archivo ya no está disponible. Súbalo de nuevo.');
            $this->paso = 1;

            return null;
        }

        try {
            $datos = app(LectorHojaCalculo::class)->leer(Storage::disk(self::DISCO)->path($this->ruta), $this->hoja);
        } catch (\RuntimeException $e) {
            $this->addError('archivo', $e->getMessage());
            $this->paso = 1;

            return null;
        }

        $this->hojas = $datos['hojas'];
        if (!$datos['filas']) {
            $this->addError('archivo', 'La hoja seleccionada está vacía.');

            return null;
        }

        return $datos['filas'];
    }

    private function servicio(): ServicioImportacionProductos
    {
        return app(ServicioImportacionProductos::class);
    }

    private function borrarArchivo(): void
    {
        if ($this->ruta) {
            Storage::disk(self::DISCO)->delete($this->ruta);
            $this->ruta = null;
        }
    }

    /** Archivos de importaciones abandonadas a medias: se borran a las 24 horas. */
    private function limpiarArchivosViejos(): void
    {
        $disco = Storage::disk(self::DISCO);
        foreach ($disco->files(self::CARPETA) as $archivo) {
            if ($disco->lastModified($archivo) < now()->subDay()->getTimestamp()) {
                $disco->delete($archivo);
            }
        }
    }
}

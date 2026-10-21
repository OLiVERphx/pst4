<?php

namespace App\Services\Importacion;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use RuntimeException;

/**
 * Lee una hoja de cálculo (xlsx, xls, ods o csv) y la devuelve como filas de valores.
 * No interpreta el contenido: eso lo hace el importador con el mapeo de columnas.
 */
class LectorHojaCalculo
{
    public const MAX_FILAS = 5000;
    public const MAX_COLUMNAS = 40;

    private const ERRORES_FORMULA = [
        '#N/A', '#N/D', '#NULL!', '#¡NULO!', '#DIV/0!', '#¡DIV/0!', '#VALUE!', '#¡VALOR!',
        '#REF!', '#¡REF!', '#NAME?', '#¿NOMBRE?', '#NUM!', '#¡NUM!', '#SPILL!', '#CALC!',
    ];

    /**
     * @return array{hojas: string[], filas: array<int, array<int, mixed>>}
     *         filas indexadas por número de fila de Excel (1, 2, ...) y columna (0, 1, ...).
     *         Las filas totalmente vacías se omiten.
     */
    public function leer(string $ruta, int $indiceHoja = 0): array
    {
        try {
            $lector = IOFactory::createReaderForFile($ruta);
        } catch (\Throwable) {
            throw new RuntimeException('El archivo no es una hoja de cálculo válida (se aceptan .xlsx, .xls, .ods y .csv).');
        }

        if ($lector instanceof Csv) {
            // Los CSV exportados desde Excel en español suelen venir con ";" y en Windows-1252
            $lector->setInputEncoding(Csv::GUESS_ENCODING);
            $lector->setFallbackEncoding('CP1252');
        }
        $lector->setReadDataOnly(true);
        $lector->setReadEmptyCells(false);

        try {
            $libro = $lector->load($ruta);
        } catch (\Throwable) {
            throw new RuntimeException('No se pudo leer el archivo. Verifique que no esté dañado ni protegido con contraseña.');
        }

        $hojas = $libro->getSheetNames();
        $hoja = $libro->getSheet(max(0, min($indiceHoja, count($hojas) - 1)));

        $ultimaFila = $hoja->getHighestDataRow();
        $ultimaColumna = min(Coordinate::columnIndexFromString($hoja->getHighestDataColumn()), self::MAX_COLUMNAS);

        if ($ultimaFila > self::MAX_FILAS + 20) {
            throw new RuntimeException('El archivo tiene ' . $ultimaFila . ' filas; el máximo por importación es ' . self::MAX_FILAS . '. Divídalo en varios archivos.');
        }

        $filas = [];
        for ($f = 1; $f <= $ultimaFila; $f++) {
            $fila = [];
            $vacia = true;
            for ($c = 1; $c <= $ultimaColumna; $c++) {
                $valor = $hoja->cellExists([$c, $f]) ? $this->valorCelda($hoja->getCell([$c, $f])) : null;
                $fila[] = $valor;
                if ($valor !== null) {
                    $vacia = false;
                }
            }
            if (!$vacia) {
                $filas[$f] = $fila;
            }
        }

        $libro->disconnectWorksheets();

        return ['hojas' => $hojas, 'filas' => $filas];
    }

    /**
     * Valor de la celda tal como lo vería el usuario: el resultado de las fórmulas (no la fórmula),
     * textos recortados y null para lo vacío.
     */
    private function valorCelda($celda): mixed
    {
        $valor = $celda->getValue();

        if ($celda->isFormula()) {
            // Primero el último resultado guardado por Excel; si no hay, se intenta calcular
            $valor = $celda->getOldCalculatedValue();
            if ($valor === null) {
                try {
                    $valor = $celda->getCalculatedValue();
                } catch (\Throwable) {
                    $valor = null;
                }
            }
        }

        if ($valor instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
            $valor = $valor->getPlainText();
        }

        if (is_string($valor)) {
            // Espacios no separables (comunes al copiar desde páginas web) y espacios repetidos
            $valor = trim(preg_replace('/[\s\x{00A0}]+/u', ' ', StringHelper::sanitizeUTF8($valor)));
            // Errores de fórmula ("#N/A", "#¡VALOR!", "#REF!"...) se tratan como celda vacía
            if ($valor === '' || in_array(mb_strtoupper($valor), self::ERRORES_FORMULA, true)) {
                return null;
            }
        }

        return $valor;
    }
}

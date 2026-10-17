<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Servicio para generación, listado, descarga y eliminación de respaldos SQL.
 * Compatible con MySQL y SQLite (para tests).
 */
class ServicioRespaldos
{
    protected string $backupDir;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');
        if (!File::isDirectory($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    /**
     * Genera un volcado SQL completo de la base de datos actual y lo guarda en un
     * ZIP cifrado con AES-256 (contraseña BACKUP_ARCHIVE_PASSWORD). El .sql en
     * claro solo existe mientras se comprime. Se abre con 7-Zip o WinRAR.
     */
    public function crearRespaldo(): array
    {
        $password = (string) config('backup.backup.password');
        if ($password === '') {
            throw new \RuntimeException('Configura BACKUP_ARCHIVE_PASSWORD en el .env: los respaldos se guardan cifrados.');
        }

        $fecha = now()->format('Y-m-d_His');
        $nombreSql = "respaldo_smartphoneworld_{$fecha}.sql";
        $rutaSql = "{$this->backupDir}/{$nombreSql}";
        $nombreArchivo = "respaldo_smartphoneworld_{$fecha}.zip";
        $rutaCompleta = "{$this->backupDir}/{$nombreArchivo}";

        $driver = DB::getDriverName();
        $dbName = DB::getDatabaseName();

        $sql = "-- ============================================================\n";
        $sql .= "-- SmartphoneWorld Database Backup\n";
        $sql .= "-- Fecha: " . now()->toIso8601String() . "\n";
        $sql .= "-- Driver: {$driver} | Database: {$dbName}\n";
        $sql .= "-- ============================================================\n\n";

        if ($driver === 'mysql') {
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        } elseif ($driver === 'sqlite') {
            $sql .= "PRAGMA foreign_keys = OFF;\n\n";
        }

        // Tablas de la base actual, sin prefijo de esquema ("main.users" en SQLite)
        $tables = Schema::getTableListing(schemaQualified: false);

        foreach ($tables as $table) {
            // Estructura
            $sql .= "-- ------------------------------------------------------------\n";
            $sql .= "-- Estructura de tabla: {$table}\n";
            $sql .= "-- ------------------------------------------------------------\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";

            if ($driver === 'mysql') {
                $createRow = DB::select("SHOW CREATE TABLE `{$table}`");
                if (!empty($createRow)) {
                    $createArray = (array) $createRow[0];
                    $createSql = $createArray['Create Table'] ?? array_values($createArray)[1] ?? null;
                    if ($createSql) {
                        $sql .= "{$createSql};\n\n";
                    }
                }
            } elseif ($driver === 'sqlite') {
                $createRow = DB::select("SELECT sql FROM sqlite_master WHERE type='table' AND name=?", [$table]);
                if (!empty($createRow) && !empty($createRow[0]->sql)) {
                    $sql .= "{$createRow[0]->sql};\n\n";
                }
            }

            // Datos
            $count = DB::table($table)->count();
            if ($count > 0) {
                $sql .= "-- Datos de tabla: {$table} ({$count} registros)\n";

                // cursor(): recorre la tabla sin exigir orderBy (chunk() fallaba con datos)
                foreach (DB::table($table)->cursor() as $row) {
                    $rowArray = (array) $row;
                    $columns = array_keys($rowArray);
                    $escapedColumns = array_map(fn($col) => "`{$col}`", $columns);

                    $escapedValues = array_map(function ($val) {
                        if (is_null($val)) {
                            return 'NULL';
                        }
                        if (is_numeric($val) && !is_string($val)) {
                            return $val;
                        }
                        return "'" . addslashes((string) $val) . "'";
                    }, array_values($rowArray));

                    $sql .= "INSERT INTO `{$table}` (" . implode(', ', $escapedColumns) . ") VALUES (" . implode(', ', $escapedValues) . ");\n";
                }
                $sql .= "\n";
            }
        }

        if ($driver === 'mysql') {
            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        } elseif ($driver === 'sqlite') {
            $sql .= "PRAGMA foreign_keys = ON;\n";
        }

        File::put($rutaSql, $sql);
        unset($sql);

        try {
            $zip = new \ZipArchive();
            if ($zip->open($rutaCompleta, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true
                || ! $zip->addFile($rutaSql, $nombreSql)
                || ! $zip->setEncryptionName($nombreSql, \ZipArchive::EM_AES_256, $password)
                || ! $zip->close()) {
                throw new \RuntimeException('No se pudo crear el ZIP cifrado del respaldo.');
            }
        } finally {
            File::delete($rutaSql);
        }

        $tamano = File::size($rutaCompleta);

        return [
            'archivo' => $nombreArchivo,
            'ruta' => $rutaCompleta,
            'tamano' => $tamano,
            'tamano_humano' => $this->formatearTamano($tamano),
            'fecha' => now(),
        ];
    }

    /**
     * Lista todos los archivos de respaldo existentes en storage/app/backups.
     */
    public function listarRespaldos(): array
    {
        if (!File::isDirectory($this->backupDir)) {
            return [];
        }

        $archivos = File::files($this->backupDir);
        $respaldos = [];

        foreach ($archivos as $archivo) {
            // .zip cifrados; .sql de respaldos anteriores a la versión cifrada
            if (in_array($archivo->getExtension(), ['zip', 'sql'], true)) {
                $tamano = $archivo->getSize();
                $respaldos[] = [
                    'nombre' => $archivo->getFilename(),
                    'tamano' => $tamano,
                    'tamano_humano' => $this->formatearTamano($tamano),
                    'fecha_modificacion' => date('d/m/Y H:i:s', $archivo->getMTime()),
                    'timestamp' => $archivo->getMTime(),
                ];
            }
        }

        // Ordenar por más reciente primero
        usort($respaldos, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return $respaldos;
    }

    /**
     * Elimina un archivo de respaldo específico.
     */
    public function eliminarRespaldo(string $nombreArchivo): bool
    {
        $sanitizado = basename($nombreArchivo);
        $ruta = "{$this->backupDir}/{$sanitizado}";

        if (File::exists($ruta)) {
            return File::delete($ruta);
        }

        return false;
    }

    /**
     * Prepara la descarga segura de un archivo de respaldo.
     */
    public function descargarRespaldo(string $nombreArchivo): BinaryFileResponse
    {
        $sanitizado = basename($nombreArchivo);
        $ruta = "{$this->backupDir}/{$sanitizado}";

        if (!File::exists($ruta)) {
            abort(404, 'Archivo de respaldo no encontrado.');
        }

        return response()->download($ruta, $sanitizado, [
            'Content-Type' => str_ends_with($sanitizado, '.zip') ? 'application/zip' : 'application/sql',
        ]);
    }

    private function formatearTamano(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}

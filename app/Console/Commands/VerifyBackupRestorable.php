<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Illuminate\Support\Str;

/**
 * Comando Artisan para verificar que el último backup es "restorable".
 *
 * NOTA: Este comando no restaura nada en la base de datos. Verifica que el
 * archivo de backup exista, sea un ZIP válido y contenga al menos un dump de
 * base de datos (.sql o .sql.gz) y la carpeta de comprobantes en su interior.
 */
class VerifyBackupRestorable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:verify {--disk=backups}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica que el último backup sea restaurable (integridad básica)';

    public function handle()
    {
        $diskName = $this->option('disk');

        $this->info("Verificando último backup en disco: {$diskName}");

        $disk = Storage::disk($diskName);

        if (! $disk) {
            $this->error("Disco '{$diskName}' no está configurado en config/filesystems.php");
            return 1;
        }

        // Intentar listar archivos y encontrar el más reciente con extensión zip
        $files = $disk->files('');

        if (empty($files)) {
            $this->error('No se encontraron archivos en el disco de backups.');
            return 2;
        }

        // Filtrar zips
        $zipFiles = array_filter($files, function ($f) {
            return Str::endsWith($f, '.zip') || Str::endsWith($f, '.zip.gpg');
        });

        if (empty($zipFiles)) {
            $this->error('No se encontraron archivos ZIP de backup en el disco de backups.');
            return 3;
        }

        // Encontrar el más reciente por lastModified
        $latest = null;
        $latestTime = 0;

        foreach ($zipFiles as $f) {
            try {
                $time = $disk->lastModified($f);
            } catch (\Exception $e) {
                // Si el driver no soporta lastModified, seguir adelante
                $time = 0;
            }

            if ($time > $latestTime) {
                $latestTime = $time;
                $latest = $f;
            }
        }

        if (! $latest) {
            // fallback: tomar el primero
            $latest = array_values($zipFiles)[0];
        }

        $this->info("Archivo elegido para verificación: {$latest}");

        // Descargar a archivo temporal
        $tempPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'backup_verify_'.uniqid().'.zip';

        try {
            $stream = $disk->readStream($latest);
            if ($stream === false) {
                $this->error('No fue posible abrir el stream del archivo en el disco remoto.');
                return 4;
            }

            $temp = fopen($tempPath, 'w+b');
            while (! feof($stream)) {
                fwrite($temp, fread($stream, 1024 * 8));
            }
            fclose($temp);
            if (is_resource($stream)) fclose($stream);
        } catch (\Exception $e) {
            $this->error('Error al descargar el archivo de backup: '.$e->getMessage());
            return 5;
        }

        // Verificar ZIP
        $zip = new ZipArchive();
        $res = $zip->open($tempPath);
        if ($res !== true) {
            $this->error('El archivo descargado no es un ZIP válido o está corrupto. Código: '.$res);
            @unlink($tempPath);
            return 6;
        }

        // Buscar al menos un dump SQL y la carpeta de comprobantes
        $hasSql = false;
        $hasReceipts = false;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (Str::endsWith($name, '.sql') || Str::endsWith($name, '.sql.gz')) {
                $hasSql = true;
            }

            // Comprobantes pueden estar dentro de storage/app/private/receipts o receipts/
            if (Str::contains($name, 'receipts') || Str::contains($name, 'comprobantes') ) {
                $hasReceipts = true;
            }

            if ($hasSql && $hasReceipts) break;
        }

        $zip->close();
        @unlink($tempPath);

        if (! $hasSql) {
            $this->error('No se encontró un dump de base de datos (.sql o .sql.gz) dentro del ZIP.');
            return 7;
        }

        if (! $hasReceipts) {
            $this->warn('No se detectó la carpeta de comprobantes dentro del ZIP. Verifique que storage/app/private/receipts esté incluida en la configuración de backups.');
            // Considerar esto como fallo suave; retornar código distinto a 0
            return 8;
        }

        $this->info('Verificación completada: el backup parece contener el dump y la carpeta de comprobantes.');
        return 0;
    }
}

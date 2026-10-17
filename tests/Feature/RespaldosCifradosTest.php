<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Livewire\Livewire;
use App\Models\User;
use App\Livewire\Datatables\RespaldosTable;
use App\Services\ServicioRespaldos;

/**
 * Los respaldos del panel se guardan en un ZIP cifrado (AES-256): sin la
 * contraseña no se puede leer la base aunque se robe el archivo.
 */
class RespaldosCifradosTest extends TestCase
{
    use RefreshDatabase;

    private array $creados = [];

    protected function tearDown(): void
    {
        foreach ($this->creados as $ruta) {
            File::delete($ruta);
        }
        parent::tearDown();
    }

    #[Test]
    public function el_respaldo_es_un_zip_cifrado_que_solo_abre_con_la_contrasena()
    {
        config(['backup.backup.password' => 'clave-de-prueba']);
        $this->seed();

        $info = app(ServicioRespaldos::class)->crearRespaldo();
        $this->creados[] = $info['ruta'];

        $this->assertStringEndsWith('.zip', $info['archivo']);
        $this->assertFileDoesNotExist(str_replace('.zip', '.sql', $info['ruta']), 'El .sql en claro debe borrarse');

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($info['ruta']));
        $entrada = $zip->statIndex(0);
        $this->assertSame(\ZipArchive::EM_AES_256, $entrada['encryption_method']);

        // Sin contraseña no se puede leer
        $this->assertFalse($zip->getFromIndex(0));

        // Con la contraseña sí, y contiene los datos (con la cédula cifrada, no en claro)
        $zip->setPassword('clave-de-prueba');
        $sql = $zip->getFromIndex(0);
        $zip->close();
        $this->assertStringContainsString('INSERT INTO `users`', $sql);
        $this->assertStringContainsString('carlos.gonzalez@example.com', $sql);
        $this->assertStringNotContainsString('10123456', $sql);
    }

    #[Test]
    public function sin_contrasena_configurada_no_se_genera_un_respaldo_en_claro()
    {
        config(['backup.backup.password' => null]);
        $this->seed();
        $antes = count(app(ServicioRespaldos::class)->listarRespaldos());

        $superadmin = User::where('email', 'admin@swworld.com')->firstOrFail();
        Livewire::actingAs($superadmin)->test(RespaldosTable::class)->call('generar');

        $this->assertSame($antes, count(app(ServicioRespaldos::class)->listarRespaldos()));

        // El motivo se le explica al superadmin
        $this->expectExceptionMessage('BACKUP_ARCHIVE_PASSWORD');
        app(ServicioRespaldos::class)->crearRespaldo();
    }
}

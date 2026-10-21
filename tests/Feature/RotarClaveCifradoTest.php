<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class RotarClaveCifradoTest extends TestCase
{
    use RefreshDatabase;

    private function usarClaves(string $actual, array $anteriores = []): void
    {
        config(['app.key' => $actual, 'app.previous_keys' => $anteriores]);
        app()->forgetInstance('encrypter');
        Crypt::clearResolvedInstance('encrypter');
    }

    public function test_rotar_la_clave_conserva_los_datos_y_la_busqueda_por_cedula(): void
    {
        $vieja = 'base64:' . base64_encode(random_bytes(32));
        $nueva = 'base64:' . base64_encode(random_bytes(32));

        $this->usarClaves($vieja);
        $user = User::factory()->create(['cedula' => 'V-12345678', 'telefono' => '04141234567', 'direccion' => 'Av. Bolívar']);

        // Sin la clave anterior, el comando se niega
        $this->usarClaves($nueva);
        $this->artisan('seguridad:rotar-clave')->assertFailed();

        $this->usarClaves($nueva, [$vieja]);
        $this->artisan('seguridad:rotar-clave')->assertSuccessful();

        // Solo con la clave nueva: los datos se leen y la cédula se encuentra
        $this->usarClaves($nueva);
        $encontrado = User::porCedula('V-12345678')->first();
        $this->assertNotNull($encontrado);
        $this->assertSame($user->id, $encontrado->id);
        $this->assertSame('Av. Bolívar', $encontrado->direccion);
        $this->assertSame($user->id, User::porTelefono('04141234567')->value('id'));
    }
}

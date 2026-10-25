<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoApiTransport;
use Tests\TestCase;

class CorreoBrevoTest extends TestCase
{
    public function test_el_mailer_brevo_usa_la_api_https(): void
    {
        config(['mail.default' => 'brevo', 'services.brevo.key' => 'clave-de-prueba']);

        $this->assertInstanceOf(BrevoApiTransport::class, Mail::mailer('brevo')->getSymfonyTransport());
    }

    public function test_el_mailer_resend_queda_disponible(): void
    {
        config(['mail.default' => 'resend', 'services.resend.key' => 're_prueba']);

        $this->assertInstanceOf(\Illuminate\Mail\Transport\ResendTransport::class, Mail::mailer('resend')->getSymfonyTransport());
    }

    public function test_el_comando_de_prueba_avisa_cuando_el_correo_no_sale(): void
    {
        config(['mail.default' => 'log']);

        $this->artisan('correo:probar', ['email' => 'ana@prueba.test'])
            ->expectsOutputToContain('solo se escribe en el registro')
            ->assertSuccessful();
    }
}

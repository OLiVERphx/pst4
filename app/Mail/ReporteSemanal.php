<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Resumen de la semana anterior para el personal con permiso reportes.ver. */
class ReporteSemanal extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $resumen)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reporte semanal ' . $this->resumen['desde']->format('d/m') . ' al ' . $this->resumen['hasta']->format('d/m/Y'),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'correos.reporte-semanal');
    }
}

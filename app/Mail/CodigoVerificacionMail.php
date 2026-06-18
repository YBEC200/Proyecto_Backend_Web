<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CodigoVerificacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public $nombre;
    public $codigo;

    public function __construct($nombre, $codigo)
    {
        $this->nombre = $nombre;
        $this->codigo = $codigo;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu código de verificación de cuenta 🔑',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.codigo_verificacion',
        );
    }
}

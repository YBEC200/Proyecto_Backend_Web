<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BienvenidaMail extends Mailable
{
    use Queueable, SerializesModels;

    // Declaramos la propiedad pública para que esté disponible en la vista Blade
    public $user;

    /**
     * Crear una nueva instancia del mailable.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Configurar el asunto (Subject) del correo.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¡Te damos la bienvenida a bordo! 🚀',
        );
    }

    /**
     * Definir qué plantilla Blade va a renderizar este correo.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.bienvenida', // Apunta a resources/views/emails/bienvenida.blade.php
        );
    }
}

<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RecuperarContraseñaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $enlace;

    public function __construct(User $user, $enlace)
    {
        $this->user = $user;
        $this->enlace = $enlace;
    }

    public function envelope()
    {
        return new \Illuminate\Mail\Mailables\Envelope(
            subject: 'Recuperar tu contraseña - CDTECH',
        );
    }

    public function content()
    {
        return new \Illuminate\Mail\Mailables\Content(
            view: 'emails.recuperar-contraseña',
            with: [
                'user' => $this->user,
                'enlace' => $this->enlace,
            ],
        );
    }
}
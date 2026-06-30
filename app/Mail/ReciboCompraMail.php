<?php

namespace App\Mail;

use App\Models\Sell;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReciboCompraMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $venta;

    public function __construct(User $user, Sell $venta)
    {
        $this->user = $user;
        $this->venta = $venta;
    }

    public function envelope()
    {
        return new \Illuminate\Mail\Mailables\Envelope(
            subject: '¡Confirmación de tu Compra!',
        );
    }

    public function content()
    {
        return new \Illuminate\Mail\Mailables\Content(
            view: 'emails.recibo-compra',
            with: [
                'user' => $this->user,
                'venta' => $this->venta,
            ],
        );
    }
}
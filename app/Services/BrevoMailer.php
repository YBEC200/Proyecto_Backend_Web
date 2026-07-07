<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Mail\Mailable;

class BrevoMailer
{
    protected Client $client;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client();
    }

    public function send(Mailable $mailable, string $toEmail, ?string $toName = null): array
    {
        $subject = $mailable->envelope()->subject ?? 'Correo de la aplicación';
        $html = $mailable->render();

        $response = $this->client->post('https://api.brevo.com/v3/smtp/email', [
            'headers' => [
                'accept' => 'application/json',
                'api-key' => env('BREVO_API_KEY'),
                'content-type' => 'application/json',
            ],
            'json' => [
                'sender' => [
                    'name' => config('mail.from.name', 'CDTECH'),
                    'email' => config('mail.from.address', env('MAIL_FROM_ADDRESS')),
                ],
                'to' => [[
                    'email' => $toEmail,
                    'name' => $toName ?? $toEmail,
                ]],
                'subject' => $subject,
                'htmlContent' => $html,
            ],
        ]);

        return json_decode((string) $response->getBody(), true) ?? [];
    }
}
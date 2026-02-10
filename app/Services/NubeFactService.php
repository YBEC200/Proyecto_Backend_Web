<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class NubeFactService
{
    public function emitirComprobante(array $data)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.nubefact.token'),
            'Content-Type'  => 'application/json',
        ])->post(config('services.nubefact.url'), $data);

        if ($response->failed()) {
            throw new \Exception($response->body());
        }

        return $response->json();
    }
}

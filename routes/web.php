<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use GuzzleHttp\Client;

Route::get('/test-mail', function () {
    try {
        $client = new Client();
        $response = $client->post('https://api.brevo.com/v3/smtp/email', [
            'headers' => [
                'accept' => 'application/json',
                'api-key' => env('BREVO_API_KEY'),
                'content-type' => 'application/json',
            ],
            'json' => [
                'sender' => [
                    'name' => 'CDTECH',
                    'email' => 'i2322007@continental.edu.pe',
                ],
                'to' => [
                    [
                        'email' => 'ybec200@gmail.com',
                        'name' => 'Prueba',
                    ],
                ],
                'subject' => 'Prueba de correo con Brevo API',
                'htmlContent' => '<h1>Hola desde Brevo API 🚀</h1>',
            ],
        ]);

        return response()->json([
            'success' => true,
            'status' => $response->getStatusCode(),
            'body' => json_decode($response->getBody(), true),
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
});

Route::get('/', function () {
    return view('welcome');
});

Route::get('/hola', function () {
    return 'Hola, bienvenido a la aplicación Laravel!';
});

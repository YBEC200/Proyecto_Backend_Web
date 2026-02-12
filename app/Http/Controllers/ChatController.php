<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Category;

class ChatController extends Controller
{

    public function chat(Request $request)
    {
        set_time_limit(660); 
        $request->validate([
            'message' => 'required|string|min:3',
        ]);

        // Toma el token tal cual lo envió el frontend: "Authorization: Bearer ..."
        $token = $request->bearerToken();

        try {
            Log::info('Forwarding to RAG, url: ' . config('services.rag.url'));
            Log::info('Forwarding token (first 10 chars): ' . substr($token ?? '', 0, 10));

            $ragResponse = Http::timeout(600) // espera respuesta completa
                ->withOptions(['connect_timeout' => 10])
                ->withToken($token)
                ->post(config('services.rag.url') . '/chat/', [
                    'message' => $request->message
                ]);

            Log::info('RAG status: '.$ragResponse->status());
            Log::info('RAG body length: '.strlen($ragResponse->body()));

            if ($ragResponse->failed()) {
                return response()->json(['answer' => 'Error al conectar con el servicio RAG.'], $ragResponse->status());
            }

            return response()->json(['answer' => $ragResponse->json('answer') ?? 'Sin respuesta']);
        } catch (\Throwable $e) {
            Log::error('ChatController error: '.$e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json(['answer' => 'Error interno: '.$e->getMessage()], 500);
        }
    }
}

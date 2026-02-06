<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    private function detectIntent(string $message): string
    {
        $message = strtolower($message);

        $intents = [
            'precio' => ['precio', 'cuesta', 'vale', 'costo'],
            'stock' => ['stock', 'hay', 'disponible', 'existencias'],
            'caracteristicas' => ['características', 'especificaciones', 'velocidad', 'capacidad'],
            'compatibilidad' => ['compatible', 'funciona', 'sirve'],
        ];

        foreach ($intents as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($message, $keyword)) {
                    return $intent;
                }
            }
        }

        return 'general';
    }

    private function getProducts(string $message)
    {
        return Product::where('estado', 'Activo')
            ->where(function ($q) use ($message) {
                $q->where('nombre', 'like', "%$message%")
                  ->orWhere('marca', 'like', "%$message%");
            })
            ->with(['lote' => function ($q) {
                $q->where('Estado', 'Activo')
                  ->where('Cantidad', '>', 0);
            }])
            ->get();
    }

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|min:3',
        ]);

        $user = Auth::user();
        $message = $request->message;

        $intent = $this->detectIntent($message);
        $products = $this->getProducts($message);

        if ($products->isEmpty()) {
            return response()->json([
                'intent' => $intent,
                'answer' => 'No encontré productos relacionados con tu consulta.'
            ]);
        }

        // 🔹 Contexto limpio para el RAG
        $context = $products->map(function ($product) {
            return [
                'nombre' => $product->nombre,
                'marca' => $product->marca,
                'precio' => $product->costo_unit,
                'stock' => $product->lotes->sum('Cantidad'),
            ];
        })->values();

        // 🔹 Llamada al microservicio RAG
        $ragResponse = Http::post(
            config('services.rag.url') . '/chat',
            [
                'user_message' => $message,
                'intent' => $intent,
                'products' => $context,
                'user' => [
                    'id' => $user->id,
                    'rol' => $user->rol,
                ],
            ]
        );

        return $ragResponse->json();
    }
}

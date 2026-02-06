<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Product;

class ChatDataController extends Controller
{
    public function productos()
    {
        return Product::where('estado', 'Abastecido')
            ->with([
                'categoria',
                'lote' => function ($q) {
                    $q->where('Estado', 'Activo')
                      ->where('Cantidad', '>', 0);
                }
            ])
            ->get()
            ->map(function ($p) {

                $stockTotal = $p->lote->sum('Cantidad');

                return [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'marca' => $p->marca,
                    'descripcion' => $p->descripcion,
                    'categoria' => $p->categoria?->Nombre,
                    'precio' => $p->costo_unit,
                    'stock' => $stockTotal,
                ];
            })
            ->values();
    }
}


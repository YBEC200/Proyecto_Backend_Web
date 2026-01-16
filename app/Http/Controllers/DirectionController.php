<?php

namespace App\Http\Controllers;

use App\Models\Direction;
use Illuminate\Http\Request;

class DirectionController extends Controller
{
    /**
     * Crear una nueva dirección con IP y captura
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ciudad' => 'required|string|max:100',
            'calle' => 'required|string|max:255',
            'referencia' => 'nullable|string|max:255',
        ]);
        try {
            // Crear dirección
            $direction = Direction::create([
                'ciudad' => $validated['ciudad'],
                'calle' => $validated['calle'],
                'referencia' => $validated['referencia'] ?? null,
            ]);

            return response()->json([
                'message' => 'Dirección guardada correctamente',
                'id' => $direction->id
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al guardar la dirección',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

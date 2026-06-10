<?php

namespace App\Http\Controllers;

use App\Models\Direction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DirectionController extends Controller
{
    /**
     * Crear una nueva dirección con validaciones mejoradas
     */
    public function store(Request $request)
    {
        // ✅ Validaciones más robustas
        $validated = $request->validate([
            'ciudad' => 'required|string|min:2|max:100',
            'calle' => 'required|string|min:3|max:255',  // ✅ Ahora es requerida
            'referencia' => 'nullable|string|max:255',
        ], [
            'ciudad.required' => 'La ciudad es obligatoria.',
            'ciudad.min' => 'La ciudad debe tener al menos 2 caracteres.',
            'calle.required' => 'La calle es obligatoria.',
            'calle.min' => 'La calle debe tener al menos 3 caracteres.',
            'referencia.max' => 'La referencia no puede exceder 255 caracteres.',
        ]);

        try {
            // ✅ Validar que no sean solo espacios en blanco
            $validated['ciudad'] = trim($validated['ciudad']);
            $validated['calle'] = trim($validated['calle']);
            
            if (empty($validated['ciudad']) || empty($validated['calle'])) {
                return response()->json([
                    'message' => 'Los campos no pueden contener solo espacios en blanco',
                    'errors' => [
                        'ciudad' => 'Campo requerido',
                        'calle' => 'Campo requerido',
                    ]
                ], 422);
            }

            // 🔍 Crear dirección
            $direction = Direction::create([
                'ciudad' => $validated['ciudad'],
                'calle' => $validated['calle'],
                'referencia' => !empty($validated['referencia']) 
                    ? trim($validated['referencia']) 
                    : null,
            ]);

            // 🔍 Validar que el ID fue generado correctamente
            $direccionId = $direction->id;
            
            // Logging para debugging
            \Log::info('Dirección guardada exitosamente', [
                'id' => $direccionId,
                'tipo_id' => gettype($direccionId),
                'es_entero' => is_int($direccionId),
                'ciudad' => $direction->ciudad,
                'calle' => $direction->calle,
            ]);

            // ✅ Respuesta garantizada con ID válido
            return response()->json([
                'success' => true,
                'message' => 'Dirección guardada correctamente',
                'id' => (int) $direccionId,  // 🔴 CRÍTICO: Convertir a entero explícitamente
                'data' => [
                    'id' => (int) $direccionId,
                    'ciudad' => $direction->ciudad,
                    'calle' => $direction->calle,
                    'referencia' => $direction->referencia,
                    'created_at' => $direction->created_at,
                ]
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Error al guardar dirección', [
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'línea' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la dirección',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

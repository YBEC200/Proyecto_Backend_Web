<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lote;
use App\Models\Product;

class LoteController extends Controller
{
    // Listar lotes con filtros
    public function index(Request $request)
    {
        $query = Lote::with('producto');

        if ($request->filled('product_id')) {
            $query->where('Id_Producto', $request->input('product_id'));
        }

        // Filtrar por nombre del lote (parcial)
        if ($request->filled('lote')) {
            $query->where('Lote', 'like', '%' . $request->input('lote') . '%');
        }

        // Rango de cantidad
        if ($request->filled('min_cantidad')) {
            $query->where('Cantidad', '>=', (int) $request->input('min_cantidad'));
        }
        if ($request->filled('max_cantidad')) {
            $query->where('Cantidad', '<=', (int) $request->input('max_cantidad'));
        }

        // Estado exacto (si se desea case-insensitive, normalizar)
        if ($request->filled('estado')) {
            $query->where('Estado', $request->input('estado'));
        }

        // Filtrar por nombre del producto relacionado (parcial)
        if ($request->filled('product_name')) {
            $name = $request->input('product_name');
            $query->whereHas('producto', function ($q) use ($name) {
                $q->where('nombre', 'like', '%' . $name . '%');
            });
        }

        $lotes = $query->orderBy('Lote', 'desc')->get();
        return response()->json($lotes, 200);
    }

    // Crear lote
    public function store(Request $request)
    {
        $validated = $request->validate([
            'Lote' => 'required|string|max:80',
            'Id_Producto' => 'required|integer|exists:productos,id',
            'Fecha_Registro' => 'nullable|date',
            'Cantidad' => 'nullable|integer|min:0',
            'Estado' => 'nullable|in:Activo,Inactivo',
        ]);

        // Defaults
        if (empty($validated['Fecha_Registro'])) {
            $validated['Fecha_Registro'] = now()->toDateString();
        }
        $validated['Cantidad'] = $validated['Cantidad'] ?? 0;
        $validated['Estado'] = $validated['Estado'] ?? 'Activo';

        $lote = Lote::create($validated);

        return response()->json(['message' => 'Lote creado', 'lote' => $lote], 201);
    }

    public function update(Request $request, $id)
    {
        $lote = Lote::find($id);
        if (!$lote) {
            return response()->json(['message' => 'Lote no encontrado'], 404);
        }

        $validated = $request->validate([
            'Lote'           => 'sometimes|required|string|max:80',
            'Fecha_Registro' => 'sometimes|nullable|date',
            'Cantidad'       => 'sometimes|required|integer|min:0',
            'Estado'         => ['sometimes','required','string','in:Activo,Abastecido,Agotado,Inactivo'],
        ]);

        try {
            // solo asignar campos permitidos
            $allowed = array_intersect_key($validated, array_flip(['Lote','Fecha_Registro','Cantidad','Estado']));
            $lote->fill($allowed);
            $lote->save();

            return response()->json(['message' => 'Lote actualizado', 'lote' => $lote], 200);
        } catch (\Illuminate\Database\QueryException $qe) {
            return response()->json(['message' => 'No se puede actualizar el lote porque tiene datos relacionados'], 409);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al actualizar lote'], 500);
        }
    }

    public function destroy($id)
    {
        $lote = Lote::find($id);
        if (!$lote) {
            return response()->json(['message' => 'Lote no encontrado'], 404);
        }

        try {
            $lote->delete();
            return response()->json(['message' => 'Lote eliminado'], 200);
        } catch (\Illuminate\Database\QueryException $qe) {
            return response()->json(['message' => 'No se puede eliminar el lote porque tiene datos relacionados'], 409);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al eliminar lote'], 500);
        }
    }
}

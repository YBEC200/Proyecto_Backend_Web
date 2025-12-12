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
        if ($request->has('product_id')) {
            $query->where('Id_Producto', $request->input('product_id'));
        }

        // Filtrar por nombre del lote (campo 'Lote') - busca parcial
        if ($request->filled('lote')) {
            $query->where('Lote', 'like', '%' . $request->input('lote') . '%');
        }

        // Filtrar por nombre del producto relacionado (campo 'nombre' en productos) - busca parcial
        if ($request->filled('product_name')) {
            $name = $request->input('product_name');
            $query->whereHas('producto', function ($q) use ($name) {
                $q->where('nombre', 'like', '%' . $name . '%');
            });
        }

        $lotes = $query->orderBy('Fecha_Registro', 'desc')->get();
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

    // Actualizar lote
    public function update(Request $request, $id)
    {
        $lote = Lote::find($id);
        if (!$lote) {
            return response()->json(['message' => 'Lote no encontrado'], 404);
        }

        $validated = $request->validate([
            'Lote' => 'sometimes|required|string|max:80',
            'Id_Producto' => 'sometimes|required|integer|exists:productos,id',
            'Fecha_Registro' => 'sometimes|nullable|date',
            'Cantidad' => 'sometimes|integer|min:0',
            'Estado' => 'sometimes|in:Activo,Inactivo',
        ]);

        $lote->fill($validated);
        $lote->save();

        return response()->json(['message' => 'Lote actualizado', 'lote' => $lote], 200);
    }

    // Eliminar lote
    public function destroy($id)
    {
        $lote = Lote::find($id);
        if (!$lote) {
            return response()->json(['message' => 'Lote no encontrado'], 404);
        }

        try {
            $lote->delete();
            return response()->json(['message' => 'Lote eliminado'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar lote'], 500);
        }
    }
}

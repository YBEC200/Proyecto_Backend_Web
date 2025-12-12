<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Lote;

class CategoryController extends Controller
{
    // Mostrar todas las categorías
    public function index()
    {
        $categorias = Category::all(['id', 'nombre']);

        if (empty($categorias)) {
            return response()->json(['message' => 'No hay categorías disponibles.'], 404);
        }
        return response()->json($categorias);
    }

    // Crear una nueva categoría
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string'
        ]);

        $categoria = Category::create($request->all());
        return response()->json($categoria, 201);
    }

    // Actualizar categoría
    public function update(Request $request, $id)
    {
        $categoria = Category::find($id);
        if (!$categoria) {
            return response()->json(['message' => 'Categoría no encontrada.'], 404);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
        ]);

        $categoria->fill($validated);
        $categoria->save();

        return response()->json($categoria, 200);
    }

    // Eliminar una categoría
    public function destroy($id)
    {
        $categoria = Category::find($id);
        if (!$categoria) {
            return response()->json(['message' => 'Categoría no encontrada.'], 404);
        }

        $tieneProductos = Product::where('id_categoria', $categoria->id)->exists();
        if ($tieneProductos) {
            return response()->json(
                ['message' => 'No se puede eliminar la categoría porque tiene productos vinculados.'],
                409
            );
        }

        try {
            $categoria->delete();
            return response()->json(['message' => 'Categoría eliminada correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar la categoría.'], 500);
        }
    }
}

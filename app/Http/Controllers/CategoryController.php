<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    // Mostrar todas las categorías
    public function index()
    {
        $categorias = Category::all(['id', 'nombre']);
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
}

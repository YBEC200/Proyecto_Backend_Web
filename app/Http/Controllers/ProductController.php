<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Lote;
use App\Models\DetailSell;

class ProductController extends Controller
{
    // Listar productos con filtros
    public function index(Request $request)
    {
        $query = Product::with(['categoria', 'lote']);

        if ($request->filled('nombre')) {
            $query->where('nombre', 'like', '%' . $request->nombre . '%');
        }
        if ($request->filled('precio_min')) {
            $query->where('costo_unit', '>=', $request->precio_min);
        }
        if ($request->filled('precio_max')) {
            $query->where('costo_unit', '<=', $request->precio_max);
        }
        if ($request->filled('categoria')) {
            $query->where('id_categoria', $request->categoria);
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $productos = $query->get()->map(function ($producto) {
            $producto->categoria_nombre = $producto->categoria ? $producto->categoria->Nombre : null;
            $producto->fecha_ultimo_lote = $producto->lote->isEmpty() ? null : $producto->lote->sortByDesc('Fecha_Registro')->first()->Fecha_Registro;
            return $producto;
        });

        return response()->json($productos);
    }

    // Crear producto
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:150',
            'descripcion' => 'nullable|string',
            'marca' => 'nullable|string|max:100',
            'id_categoria' => 'required|integer|exists:categoria,id',
            'estado' => 'required|in:Agotado,Abastecido,Inactivo',
            'costo_unit' => 'required|numeric|min:0',
            'imagen_path' => 'nullable|string|max:255',
            'fecha_registro' => 'nullable|date'
        ]);

        $producto = Product::create($request->all());

        return response()->json(['message' => 'Producto creado correctamente', 'producto' => $producto], 201);
    }

    public function destroy($id)
    {
        // Buscar producto
        $producto = Product::find($id);
        if (!$producto) {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }

        // Verificar si tiene lotes vinculados (tabla 'lote' usa campo Id_Producto)
        $tieneLotes = Lote::where('Id_Producto', $producto->id)->exists();
        if ($tieneLotes) {
            return response()->json(
                ['message' => 'No se puede eliminar el producto porque tiene lotes vinculados.'],
                409
            );
        }

        // Eliminar producto
        try {
            $producto->delete();
            return response()->json(['message' => 'Producto eliminado correctamente.'], 200);
        } catch (\Illuminate\Database\QueryException $qe) {
            return response()->json(['message' => 'No se puede eliminar el producto porque tiene datos relacionados.'], 409);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al eliminar el producto.'], 500);
        }
    }

    // Actualizar producto
    public function update(Request $request, $id)
    {
        // Validación
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'descripcion' => 'nullable|string',
            'marca' => 'nullable|string|max:100',
            'id_categoria' => 'required|integer|exists:categoria,id',
            'estado' => 'required|in:Abastecido,Agotado,Inactivo',
            'costo_unit' => 'required|numeric|min:0',
            'imagen_path' => 'nullable|string|max:255',
        ]);

        // Buscar producto
        $producto = Product::find($id);
        if (!$producto) {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }

        // Actualizar
        try {
            $producto->fill($validated);
            $producto->save();

            return response()->json(['message' => 'Producto actualizado', 'producto' => $producto], 200);
        } catch (\Illuminate\Database\QueryException $qe) {
            return response()->json(['message' => 'Error en la base de datos'], 500);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al actualizar el producto'], 500);
        }
    }
    
    public function canDelete($id)
    {
        $producto = Product::find($id);
        
        if (!$producto) {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }
        
        // Verificar si tiene lotes
        $tieneOtes = Lote::where('Id_Producto', $id)->exists();
        
        // Verificar si está en alguna venta (a través de detailVenta)
        $tieneVentas = DetailSell::where('id_producto', $id)->exists();
        
        return response()->json([
            'can_delete' => !$tieneOtes && !$tieneVentas,
            'razon' => $tieneOtes 
                ? 'producto_con_lotes' 
                : ($tieneVentas ? 'producto_con_ventas' : null)
        ]);
    }
}

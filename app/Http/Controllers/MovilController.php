<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Services\NubeFactService;
use App\Models\Category;
use App\Models\Direction;
use App\Models\Sell;
use Illuminate\Support\Facades\DB;

class MovilController extends Controller
{
    // Mostrar productos con filtros para móvil, se debe incluir el de la categoria
    // No olvides retornar las categorias para que se usen en filtros. ademas considera la tabla producto_imagenes para retornar la url de las imagenes secundarias.
    // Ojo: incluir también la fecha del último lote registrado, ademas de la url de la imagen ay el frontend se encarga de mostrarla
    // Ojo2: no se debe incluir datos de lote excepto la fecha del último lote registrado, y el stock sacado de la suma de Cantidad en los lotes.
    public function index(Request $request)
    {
        $query = Product::query()
            ->select([
                'id',
                'nombre',
                'descripcion',
                'marca',
                'id_categoria',
                'costo_unit',
                'fecha_registro',
                'imagen_path'
            ])
            ->with([
                'categoria:Id,Nombre',
                'imagenes:id,producto_id,ruta'
            ])
            // ✅ Stock = SUM(Cantidad) solo lotes activos
            ->withSum(['lote as stock' => function ($q) {
                $q->where('Estado', 'Activo');
            }], 'Cantidad')
            // ✅ Última fecha de registro de lote
            ->withMax('lote as fecha_ultimo_lote', 'Fecha_Registro');

        /*
        |--------------------------------------------------------------------------
        | Filtros dinámicos
        |--------------------------------------------------------------------------
        */

        if ($request->filled('categoria')) {
            $query->where('id_categoria', $request->categoria);
        }

        if ($request->filled('marca')) {
            $query->where('marca', 'like', '%' . $request->marca . '%');
        }

        if ($request->filled('precio_min')) {
            $query->where('costo_unit', '>=', $request->precio_min);
        }

        if ($request->filled('precio_max')) {
            $query->where('costo_unit', '<=', $request->precio_max);
        }

        return response()->json([
            'productos' => $query->get(),
            'categorias' => Category::select('Id','Nombre')->get()
        ]);
    }

    /**
     * Mostrar datos del usuario autenticado en el aplicativo móvil
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Actualizar datos del usuario autenticado en el aplicativo móvil, solo los suyos. obvio no puede cambiar su rol ni su estado,
     * mucho menos el correo a uno ya registrado(solo nombre y el correo a uno no vinculado). 
     * Ojo: si actualiza la contraseña, esta debe ser hasheada antes de guardarse, ademas de confirmada 2 veces.
     */

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'nombre' => 'sometimes|required|string|max:150',
            'correo' => 'sometimes|required|email|unique:users,correo,' . $user->id,
            'password' => 'sometimes|required|string|min:6|confirmed',
        ]);

        if ($request->filled('nombre')) {
            $user->nombre = $request->nombre;
        }
        if ($request->filled('correo')) {
            $user->correo = $request->correo;
        }
        if ($request->filled('password')) {
            $user->password_hash = bcrypt($request->password);
        }

        $user->save();

        return response()->json(['message' => 'Perfil actualizado correctamente', 'user' => $user]);
    }
}
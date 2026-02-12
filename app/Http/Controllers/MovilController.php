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
     * Registrarse en el aplicativo móvil, obteniendo un token de autenticación. ademas de crear el usuario en la base de datos(todos seran clientes, un admin no se registra por aqui)
     * protected $fillable = [
        'nombre',
        'correo',
        'password_hash',
        'rol',
        'estado',
        'fecha_registro'
        OJO: el campo 'rol' se llenara con 'Cliente' por defecto, el campo 'estado' se llenara con 'Activo' por defecto, y el campo 'fecha_registro' se llenara con la fecha actual por defecto, ademas de hashear la contraseña antes de guardarla, PERO MAS IMPORTANTE EL CORREO NO SE DEBE REPETIR SE CONSULTARA A LA DB Y SI SE REPITE SE PONDRA INVALIDO
     */

    public function register(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:150',
            'correo' => 'required|email|unique:users,correo',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'nombre' => $request->nombre,
            'correo' => $request->correo,
            'password_hash' => Hash::make($request->password),
            'rol' => 'Cliente',
            'estado' => 'Activo',
            'fecha_registro' => now(),
        ]);

        // Generar token con Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'correo' => $user->correo,
                'rol' => $user->rol
            ],
            'token' => $token
        ], 201);
    }

    /**
     * Iniciar sesión en el aplicativo móvil, obteniendo un token de autenticación(si las credenciales son correctas, y si el usuario tiene de estado 'Activo')
     */
    public function login(Request $request)
    {
        $request->validate([
            'correo' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('correo', $request->correo)
                    ->where('estado', 'Activo')
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Correo electrónico y/o contraseña incorrectos.'
            ], 401);
        }

        // Generar token con Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'correo' => $user->correo,
                'rol' => $user->rol
            ],
            'token' => $token
        ]);
    }

    /**
     * Cerrar sesión en el aplicativo móvil, revocando el token de autenticación
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada correctamente']);
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
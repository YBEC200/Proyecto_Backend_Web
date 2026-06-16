<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Sell;

class UsuarioController extends Controller
{
    // Crear un nuevo usuario
    public function store(Request $request)
    {
        // 1. Validamos los datos (Quitamos el 'unique' automático para manejarlo nosotros)
        $request->validate([
            'nombre' => 'required|string|max:150',
            'correo' => 'required|email|max:150',
            'password' => 'required|string|min:6',
            'rol' => 'required|in:Administrador,Empleado,Cliente',
        ]);

        // 2. Buscamos si ya existe un usuario con ese correo
        $usuarioExistente = User::where('correo', $request->correo)->first();

        if ($usuarioExistente) {
            // CASO A: El usuario ya existe y ya está ACTIVO.
            if ($usuarioExistente->estado === 'Activo') {
                return response()->json([
                    'message' => 'El correo electrónico ya se encuentra registrado y activo.'
                ], 422); // Error estándar de validación
            }

            // CASO B: El usuario existe pero está INACTIVO (Tu escenario planteado)
            // Generamos un NUEVO código de 6 dígitos
            $nuevoCodigo = rand(100000, 999999);
            
            // Actualizamos sus datos por si acaso el usuario quiere cambiar su nombre o contraseña en este reintento
            $usuarioExistente->nombre = $request->nombre;
            $usuarioExistente->password_hash = bcrypt($request->password);
            $usuarioExistente->rol = $request->rol;
            $usuarioExistente->codigo_verificacion = $nuevoCodigo;
            $usuarioExistente->save();

            // Reenviamos el correo con el nuevo código usando Resend
            Mail::to($usuarioExistente->correo)->send(new CodigoVerificacionMail($usuarioExistente->nombre, $nuevoCodigo));

            return response()->json([
                'message' => 'Detectamos un registro previo pendiente. Hemos reenviado un nuevo código a tu correo.',
                'correo' => $usuarioExistente->correo
            ], 200); // Retornamos 200 (Éxito de reenvío)
        }

        // CASO C: El usuario es completamente NUEVO (Primer intento)
        $codigoObtenido = rand(100000, 999999);

        $usuario = new User();
        $usuario->nombre = $request->nombre;
        $usuario->correo = $request->correo;
        $usuario->password_hash = bcrypt($request->password);
        $usuario->rol = $request->rol;
        $usuario->estado = 'Inactivo'; 
        $usuario->codigo_verificacion = $codigoObtenido; 
        $usuario->fecha_registro = now();
        $usuario->save();

        // Mail::to($usuario->correo)->send(new CodigoVerificacionMail($usuario->nombre, $codigoObtenido));

        return response()->json([
            'message' => 'Usuario registrado. Por favor, verifica tu correo electrónico.',
            'correo' => $usuario->correo
        ], 201);
    }

    // 2. Confirmación del código (Activa al usuario)
    public function verificarCodigo(Request $request)
    {
        $request->validate([
            'correo' => 'required|email',
            'codigo' => 'required|numeric',
        ]);

        // Buscamos al usuario que coincida con el correo y el código
        $usuario = User::where('correo', $request->correo)
                       ->where('codigo_verificacion', $request->codigo)
                       ->first();

        if (!$usuario) {
            return response()->json(['message' => 'El código es incorrecto o ya expiró.'], 422);
        }

        // Si coincide, lo activamos y borramos el código para que no se use de nuevo
        $usuario->estado = 'Activo';
        $usuario->codigo_verificacion = null; 
        $usuario->save();

        // OPCIONAL: Aquí podrías disparar tu "BienvenidaMail" original si quieres
        // Mail::to($usuario->correo)->send(new BienvenidaMail($usuario));

        return response()->json([
            'message' => '¡Cuenta verificada con éxito! Ya puedes iniciar sesión.',
            'usuario' => $usuario
        ], 200);
    }

    // Actualizar un usuario existente
    //Se actualizara solo los campos enviados, no es necesario enviar todos los campos para actualizar
    public function update(Request $request, $id)
    {
        $usuario = User::find($id);
        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        $data = $request->only(['nombre', 'correo', 'password', 'rol', 'estado']);

        $rules = [
            'nombre' => 'sometimes|string|max:255',
            'correo' => 'sometimes|email|max:255|unique:users,correo,' . $id,
            'password' => 'sometimes|string|min:6|confirmed',
            'rol' => 'sometimes|string|in:admin,user', // ajusta roles permitidos según tu app
            'estado' => 'sometimes|in:activo,inactivo,pendiente' // ajusta estados si hace falta
        ];

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (array_key_exists('nombre', $data)) {
            $usuario->nombre = $data['nombre'];
        }

        if (array_key_exists('correo', $data)) {
            $usuario->correo = $data['correo'];
        }

        if (array_key_exists('rol', $data)) {
            $usuario->rol = $data['rol'];
        }

        if (array_key_exists('estado', $data)) {
            $usuario->estado = $data['estado'];
        }

        if (array_key_exists('password', $data) && !empty($data['password'])) {
            $usuario->password_hash = bcrypt($data['password']);
        }

        $usuario->save();

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'usuario' => $usuario
        ], 200);
    }

    // Listar usuarios con filtros
    public function index(Request $request)
    {
        $query = User::query();

        // Filtros
        if ($request->filled('nombre')) {
            $query->where('nombre', 'like', '%' . $request->nombre . '%');
        }
        if ($request->filled('rol')) {
            $query->where('rol', $request->rol);
        }
        if ($request->filled('fecha_creacion')) {
            $query->whereDate('created_at', $request->fecha_creacion);
        }
        if ($request->filled('fecha_actualizacion')) {
            $query->whereDate('updated_at', $request->fecha_actualizacion);
        }

        $usuarios = $query->get();

        return response()->json($usuarios);
    }

    public function show($id)
    {
        // Verificar que el usuario exista
        $user = User::findOrFail($id);

        $ventas = Sell::where('Id_Usuario', $user->id)
            ->select([
                'Id as id',
                'Costo_Total as total',
                'Fecha as fecha',
                'estado',
                'tipo_entrega'
            ])
            ->orderByDesc('Fecha')
            ->get();

        return response()->json([
            'usuario' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'correo' => $user->correo,
            ],
            'ventas' => $ventas
        ]);
    }

    // Eliminar un usuario
    public function destroy($id)
    {
        $usuario = User::find($id);
        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }
        $usuario->delete();
        return response()->json(['message' => 'Usuario eliminado correctamente']);
    }

    public function canDelete($id)
    {
        $usuario = User::find($id);
        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $tieneVentas = Sell::where('Id_Usuario', $id)->exists();

        if ($tieneVentas) {
            return response()->json(['can_delete' => false, 'message' => 'El usuario tiene ventas asociadas y no puede ser eliminado.'], 200);
        } else {
            return response()->json(['can_delete' => true, 'message' => 'El usuario puede ser eliminado.'], 200);
        }
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Sell;
use App\Mail\CodigoVerificacionMail;
use App\Mail\BienvenidaMail;
use App\Services\BrevoMailer;

class UsuarioController extends Controller
{
    // Crear un nuevo usuario cliente con verificación por correo
    public function store(Request $request)
    {
        // 1. Validamos los datos (Quitamos el 'unique' automático para manejarlo nosotros)
        $request->validate([
            'nombre' => 'required|string|max:150',
            'correo' => 'required|email|max:150',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Verificar si el correo ya existe (sea activo o inactivo)
        $usuarioExistente = User::where('correo', $request->correo)->first();

        if ($usuarioExistente) {
            return response()->json([
                'message' => 'Este correo ya se encuentra registrado. Por favor, intenta iniciar sesión.'
            ], 422);
        }

        // Crear usuario nuevo con estado Inactivo
        $codigoObtenido = rand(100000, 999999);

        $usuario = User::create([
            'nombre' => $request->nombre,
            'correo' => $request->correo,
            'password_hash' => Hash::make($request->password),
            'rol' => 'Cliente',
            'estado' => 'Inactivo',
            'codigo_verificacion' => $codigoObtenido,
            'fecha_registro' => now(),
        ]);

        // Enviar correo de verificación
        app(BrevoMailer::class)->send(
            new CodigoVerificacionMail($usuario->nombre, $codigoObtenido),
            $usuario->correo,
            $usuario->nombre
        );
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
            'codigo' => 'required|numeric|digits:6',
        ]);

        $usuario = User::where('correo', $request->correo)
                       ->where('codigo_verificacion', $request->codigo)
                       ->first();

        if (!$usuario) {
            return response()->json([
                'message' => 'El código es incorrecto o ya expiró.'
            ], 422);
        }

        // Activar usuario y eliminar código
        $usuario->estado = 'Activo';
        $usuario->codigo_verificacion = null;
        $usuario->save();

        // Enviar bienvenida
        app(BrevoMailer::class)->send(
            new BienvenidaMail($usuario),
            $usuario->correo,
            $usuario->nombre
        );

        return response()->json([
            'message' => '¡Cuenta verificada con éxito! Ya puedes iniciar sesión.',
            'usuario' => [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'correo' => $usuario->correo,
            ]
        ], 200);
    }

    // 3. Login del usuario (solo si está activo)
    public function login(Request $request)
    {
        $request->validate([
            'correo' => 'required|email',
            'password' => 'required|string|min:6'
        ]);

        // Buscar usuario por correo (sin filtrar por estado)
        $usuario = User::where('correo', $request->correo)->first();

        // Validar que existe y contraseña correcta
        if (!$usuario || !Hash::check($request->password, $usuario->password_hash)) {
            return response()->json([
                'message' => 'Correo electrónico y/o contraseña incorrectos.'
            ], 401);
        }

        // CASO A: Usuario INACTIVO - Regenerar código de verificación
        if ($usuario->estado === 'Inactivo') {
            $nuevoCodigo = rand(100000, 999999);
            $usuario->codigo_verificacion = $nuevoCodigo;
            $usuario->save();

            // Enviar nuevo código
            app(BrevoMailer::class)->send(
                new CodigoVerificacionMail($usuario->nombre, $nuevoCodigo),
                $usuario->correo,
                $usuario->nombre
            );

            return response()->json([
                'message' => 'Tu cuenta aún no ha sido verificada. Hemos reenviado un código a tu correo.',
                'correo' => $usuario->correo
            ], 200);
        }

        // CASO B: Usuario ACTIVO - Devolver token
        $token = $usuario->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Sesión iniciada correctamente.',
            'user' => [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'correo' => $usuario->correo,
                'rol' => $usuario->rol
            ],
            'token' => $token
        ], 200);
    }

    /**
     * REENVIAR CÓDIGO: Permite al usuario solicitar un nuevo código de verificación
     * Solo funciona si la cuenta está INACTIVA
     */
    public function reenviarCodigo(Request $request)
    {
        $request->validate([
            'correo' => 'required|email',
        ]);

        $usuario = User::where('correo', $request->correo)->first();

        // Validar que el usuario exista
        if (!$usuario) {
            return response()->json([
                'message' => 'No encontramos una cuenta con este correo electrónico.'
            ], 404);
        }

        // Validar que la cuenta esté INACTIVA (no verificada)
        if ($usuario->estado === 'Activo') {
            return response()->json([
                'message' => 'Tu cuenta ya está verificada. Por favor, inicia sesión.'
            ], 422);
        }

        // Generar nuevo código
        $nuevoCodigo = rand(100000, 999999);
        $usuario->codigo_verificacion = $nuevoCodigo;
        $usuario->save();

        // Enviar correo con nuevo código
        app(BrevoMailer::class)->send(
            new CodigoVerificacionMail($usuario->nombre, $nuevoCodigo),
            $usuario->correo,
            $usuario->nombre
        );

        return response()->json([
            'message' => 'Hemos reenviado el código de verificación a tu correo.',
            'correo' => $usuario->correo
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
            'rol' => 'sometimes|string|in:Administrador,Empleado,Cliente', // ajusta roles permitidos según tu app
            'estado' => 'sometimes|in:Activo,Inactivo,Pendiente' // ajusta estados si hace falta
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

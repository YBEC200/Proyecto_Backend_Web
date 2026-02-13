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
        $request->validate([
            'nombre' => 'required|string|max:150',
            'correo' => 'required|email|max:150|unique:users,correo',
            'password' => 'required|string|min:6',
            'rol' => 'required|in:Administrador,Empleado,Cliente',
            'estado' => 'required|in:Activo,Inactivo',
        ]);

        $usuario = new User();
        $usuario->nombre = $request->nombre;
        $usuario->correo = $request->correo;
        $usuario->password_hash = bcrypt($request->password);
        $usuario->rol = $request->rol;
        $usuario->estado = $request->estado;
        $usuario->fecha_registro = now();
        $usuario->save();

        return response()->json(['message' => 'Usuario creado correctamente', 'usuario' => $usuario], 201);
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
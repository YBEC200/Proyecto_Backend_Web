<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UsuarioController extends Controller
{
    // Crear un nuevo usuario
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:150',
            'correo' => 'required|email|max:150|unique:users,correo',
            'rol' => 'required|in:Administrador,Empleado,Cliente',
        ]);
        if (!in_array($request->rol, ['Administrador', 'Empleado', 'Cliente'])) {
            return response()->json(['message' => 'Rol inválido'], 400);
        }
        if (User::where('correo', $request->correo)->exists()) {
            return response()->json(['message' => 'El correo ya está en uso'], 400);
        }
        $usuario = new User();
        $usuario->nombre = $request->nombre;
        $usuario->correo = $request->correo;
        $usuario->rol = $request->rol;
        $usuario->estado = 'Activo'; // Estado por defecto
        $usuario->save();
        return response()->json(['message' => 'Usuario creado correctamente', 'usuario' => $usuario], 201);
    }

    // Actualizar un usuario existente
    public function update(Request $request, $id)
    {
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $request->validate([
            'nombre' => 'required|string|max:150',
            'correo' => 'required|email|max:150|unique:users,correo,' . $id,
            'rol' => 'required|in:Administrador,Empleado,Cliente',
            'estado' => 'required|in:Activo,Inactivo',
        ]);

        $usuario->nombre = $request->nombre;
        $usuario->correo = $request->correo;
        $usuario->rol = $request->rol;
        $usuario->estado = $request->estado;
        $usuario->save();

        return response()->json(['message' => 'Usuario actualizado correctamente', 'usuario' => $usuario]);
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
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function adminLogin(Request $request)
    {
        $request->validate([
            'correo' => 'required|email',
            'password' => 'required'
        ]);
        
        $user = User::where('correo', $request->correo)
                    ->where('estado', 'Activo')
                    ->where('rol', 'Administrador')
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Solo los administradores pueden acceder a este panel.'
            ], 401);
        }

        // CASO A: Usuario INACTIVO - Regenerar código de verificación
        if ($usuario->estado === 'Inactivo') {
            $nuevoCodigo = rand(100000, 999999);
            $usuario->codigo_verificacion = $nuevoCodigo;
            $usuario->save();

            // Enviar nuevo código
            app(BrevoMailer::class)->send(
                new CodigoVerificacionMail($user->nombre, $nuevoCodigo),
                $user->correo,
                $user->nombre
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

    public function adminLogout(Request $request)
    {
        // Elimina solo el token actual
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente'
        ]);
    }
}

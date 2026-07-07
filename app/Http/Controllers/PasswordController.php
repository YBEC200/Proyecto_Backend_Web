<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Mail\RecuperarContraseñaMail;
use App\Services\BrevoMailer;

class PasswordController extends Controller
{
    /**
     * Solicitar recuperación de contraseña
     * POST /forgot-password
     */
    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'correo' => 'required|email|exists:users,correo'
        ]);

        $user = User::where('correo', $validated['correo'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        // Generar token único
        $token = Str::random(60);
        $tokenHash = Hash::make($token);

        // Guardar token en BD (válido por 1 hora)
        $user->update([
            'reset_token' => $tokenHash,
            'reset_token_expires_at' => now()->addHour()
        ]);

        // Generar enlace de recuperación
        $enlace = config('app.url') . '/reset-password?token=' . $token . '&correo=' . $user->correo;

        // Enviar email
        try {
            app(BrevoMailer::class)->send(
                new RecuperarContraseñaMail($user, $enlace),
                $user->correo,
                $user->nombre
            );
            
            \Log::info('Email de recuperación de contraseña enviado', [
                'usuario_id' => $user->id,
                'correo' => $user->correo
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Enlace de recuperación enviado a tu correo'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error al enviar email de recuperación', [
                'usuario_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el email'
            ], 500);
        }
    }

    /**
     * Validar token y restablecer contraseña
     * POST /reset-password
     */
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'correo' => 'required|email|exists:users,correo',
            'password' => 'required|string|min:6',
            'password_confirmation' => 'required|string|same:password'
        ]);

        $user = User::where('correo', $validated['correo'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        // ✅ Validar que el token no sea nulo
        if (!$user->reset_token) {
            return response()->json([
                'success' => false,
                'message' => 'No hay solicitud de recuperación activa'
            ], 400);
        }

        // ✅ Validar que el token no haya expirado
        if (!$user->reset_token_expires_at || now()->isAfter($user->reset_token_expires_at)) {
            $user->update([
                'reset_token' => null,
                'reset_token_expires_at' => null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'El enlace de recuperación ha expirado. Solicita uno nuevo.'
            ], 400);
        }

        // ✅ Validar que el token sea correcto
        if (!Hash::check($validated['token'], $user->reset_token)) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido'
            ], 400);
        }

        // ✅ Actualizar contraseña
        $user->update([
            'password_hash' => Hash::make($validated['password']),
            'reset_token' => null,
            'reset_token_expires_at' => null
        ]);

        \Log::info('Contraseña restablecida', [
            'usuario_id' => $user->id,
            'correo' => $user->correo
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente. Puedes iniciar sesión.'
        ], 200);
    }
}
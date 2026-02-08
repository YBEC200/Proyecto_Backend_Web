<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Alert;

class AlertController extends Controller
{
    /**
     * Listar alertas (con filtros opcionales)
     */
    public function index(Request $request)
    {
        $query = Alert::query();

        /* =============================
        * Filtros
        * ============================= */

        if ($request->filled('leida')) {
            $query->where('leida', $request->boolean('leida'));
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('severidad')) {
            $query->where('severidad', $request->severidad);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        /* =============================
        * Relaciones a cargar
        * ============================= */

        $query->with([
            'user:id,nombre,correo',

            // Venta (si la alerta es de tipo VENTA)
            'venta' => function ($q) {
                $q->select(
                    'Id',
                    'Id_Usuario',
                    'Fecha',
                    'Costo_Total',
                    'estado'
                )->with([
                    'user:id,nombre,correo',
                    'details.product:id,nombre'
                ]);
            },

            // Producto (si la alerta es de tipo PRODUCTO)
            'producto:id,nombre,estado',

            // Lote (cuando aplique)
            'lote:Id,Lote,Cantidad,Estado,Id_Producto'
        ]);

        /* =============================
        * Respuesta
        * ============================= */

        return response()->json(
            $query
                ->orderByDesc('created_at')
                ->paginate(15)
        );
    }

    /**
     * Listar solo alertas no leídas (para el badge en el frontend)
     */
    public function unreadIndex()
    {
        $alerts = Alert::where('leida', false)
            ->with(['venta:id,Id_Usuario,Fecha,Costo_Total,estado',
                    'producto:id,nombre,estado',
                    'lote:Id,Lote,Cantidad,Estado,Id_Producto'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($alerts);
    }

    /**
     * Marcar una alerta como leída
     */
    public function markAsRead($id)
    {
        $alert = Alert::findOrFail($id);
        $alert->leida = true;
        $alert->save();

        return response()->json([
            'message' => 'Alerta marcada como leída',
        ]);
    }

    /**
     * Marcar todas las alertas como leídas
     */
    public function markAllAsRead()
    {
        Alert::where('leida', false)->update([
            'leida' => true,
        ]);

        return response()->json([
            'message' => 'Todas las alertas fueron marcadas como leídas',
        ]);
    }

    /**
     * Contador rápido de alertas no leídas
     */
    public function unreadCount()
    {
        return response()->json([
            'unread' => Alert::where('leida', false)->count(),
        ]);
    }

    /**
     * Contador rápido de todas las alertas (opcional, para dashboard)
     */
    public function totalCount()
    {
        return response()->json([
            'total' => Alert::count(),
        ]);
    }
    
    public function destroy($id)
    {
        $alert = Alert::findOrFail($id);
        $alert->delete();

        return response()->json([
            'message' => 'Alerta eliminada correctamente',
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Sell;
use App\Models\User;
use App\Models\Direction;
use App\Models\DetailSell;
use App\Models\DetailLote;
use App\Models\Lote;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SellController extends Controller
{
    /**
     * Obtener todas las ventas
     */
    public function index()
    {
        $sells = Sell::with(['user', 'direction', 'details.product', 'details.detailLotes.lote'])->get();
        return response()->json($sells, 200);
    }

    /**
     * Obtener una venta por ID
     */
    public function show($id)
    {
        $sell = Sell::with(['user', 'direction', 'details.product', 'details.detailLotes.lote'])->find($id);

        if (!$sell) {
            return response()->json(['message' => 'Venta no encontrada'], 404);
        }

        return response()->json($sell, 200);
    }

    /**
     * Crear una nueva venta con detalles y descuentos de lote
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_usuario' => 'required|exists:users,id',
            'metodo_pago' => 'required|in:Efectivo,Tarjeta,Deposito,Yape',
            'comprobante' => 'required|in:Boleta,Factura',
            'id_direccion' => 'nullable|exists:directions,id',
            'ciudad' => 'nullable|string|max:100',
            'calle' => 'nullable|string|max:255',
            'referencia' => 'nullable|string|max:255',
            'costo_total' => 'required|numeric|min:0',
            'estado' => 'required|in:Cancelado,Entregado,Pendiente',
            'details' => 'required|array|min:1',
            'details.*.id_producto' => 'required|exists:productos,id',
            'details.*.cantidad' => 'required|integer|min:1',
            'details.*.costo' => 'required|numeric|min:0'
        ]);

        // Usar transacción para garantizar consistencia
        return DB::transaction(function () use ($validated) {
            // Validar que hay suficiente cantidad en lotes
            foreach ($validated['details'] as $detail) {
                $product = Product::find($detail['id_producto']);
                $cantidadDisponible = Lote::where('Id_Producto', $detail['id_producto'])
                    ->where('Estado', 'Disponible')
                    ->sum('Cantidad');

                if ($cantidadDisponible < $detail['cantidad']) {
                    throw new \Exception("Producto '{$product->nombre}' no tiene suficiente cantidad disponible. Disponible: {$cantidadDisponible}, Solicitado: {$detail['cantidad']}");
                }
            }

            // Crear dirección si se proporcionan los datos
            $idDireccion = $validated['id_direccion'] ?? null;
            if (!$idDireccion && ($validated['ciudad'] || $validated['calle'] || $validated['referencia'])) {
                $direction = Direction::create([
                    'ciudad' => $validated['ciudad'] ?? null,
                    'calle' => $validated['calle'] ?? null,
                    'referencia' => $validated['referencia'] ?? null
                ]);
                $idDireccion = $direction->id;
            }

            // Crear la venta
            $sell = Sell::create([
                'id_usuario' => $validated['id_usuario'],
                'metodo_pago' => $validated['metodo_pago'],
                'comprobante' => $validated['comprobante'],
                'id_direccion' => $idDireccion,
                'fecha' => now(),
                'costo_total' => $validated['costo_total'],
                'estado' => $validated['estado']
            ]);

            // Crear detalles de venta y detalles de lote
            foreach ($validated['details'] as $detail) {
                $detailSell = DetailSell::create([
                    'id_venta' => $sell->id,
                    'id_producto' => $detail['id_producto'],
                    'cantidad' => $detail['cantidad'],
                    'costo' => $detail['costo']
                ]);

                // Descontar cantidad de los lotes
                $cantidadFaltante = $detail['cantidad'];
                $lotes = Lote::where('Id_Producto', $detail['id_producto'])
                    ->where('Estado', 'Disponible')
                    ->orderBy('Fecha_Registro', 'asc')
                    ->get();

                foreach ($lotes as $lote) {
                    if ($cantidadFaltante <= 0) {
                        break;
                    }

                    $cantidadADescontar = min($cantidadFaltante, $lote->Cantidad);

                    // Crear registro en detalle_lote
                    DetailLote::create([
                        'id_detalle_venta' => $detailSell->id,
                        'id_lote' => $lote->Id,
                        'cantidad' => $cantidadADescontar
                    ]);

                    // Actualizar cantidad en el lote
                    $lote->Cantidad -= $cantidadADescontar;
                    
                    // Si el lote llega a 0, cambiar estado a "Agotado"
                    if ($lote->Cantidad <= 0) {
                        $lote->Estado = 'Agotado';
                    }
                    
                    $lote->save();
                    $cantidadFaltante -= $cantidadADescontar;
                }
            }

            return response()->json($sell->load(['user', 'direction', 'details.product', 'details.detailLotes.lote']), 201);
        });
    }

    /**
     * Actualizar una venta
     */
    public function update(Request $request, $id)
    {
        $sell = Sell::find($id);

        if (!$sell) {
            return response()->json(['message' => 'Venta no encontrada'], 404);
        }

        $validated = $request->validate([
            'id_usuario' => 'sometimes|required|exists:users,id',
            'metodo_pago' => 'sometimes|required|in:Efectivo,Tarjeta,Deposito,Yape',
            'comprobante' => 'sometimes|required|in:Boleta,Factura',
            'id_direccion' => 'sometimes|required|exists:directions,id',
            'costo_total' => 'sometimes|required|numeric|min:0',
            'estado' => 'sometimes|required|in:Cancelado,Entregado,Pendiente'
        ]);

        $sell->update($validated);

        return response()->json($sell->load(['user', 'direction', 'details.product', 'details.detailLotes.lote']), 200);
    }

    /**
     * Eliminar una venta (devuelve productos a lotes)
     */
    public function destroy($id)
    {
        $sell = Sell::find($id);

        if (!$sell) {
            return response()->json(['message' => 'Venta no encontrada'], 404);
        }

        return DB::transaction(function () use ($sell, $id) {
            // Obtener todos los detalles de lote de esta venta
            $detailLotes = DetailLote::whereHas('detailSell', function ($query) use ($id) {
                $query->where('id_venta', $id);
            })->get();

            // Devolver cantidad a los lotes
            foreach ($detailLotes as $detailLote) {
                $lote = Lote::find($detailLote->id_lote);
                if ($lote) {
                    $lote->Cantidad += $detailLote->cantidad;
                    
                    // Si estaba agotado y ahora tiene cantidad, cambiar a Disponible
                    if ($lote->Estado === 'Agotado' && $lote->Cantidad > 0) {
                        $lote->Estado = 'Disponible';
                    }
                    
                    $lote->save();
                }
            }

            // Eliminar detalles de lote
            DetailLote::whereHas('detailSell', function ($query) use ($id) {
                $query->where('id_venta', $id);
            })->delete();

            // Eliminar detalles de venta
            DetailSell::where('id_venta', $id)->delete();

            // Eliminar venta
            $sell->delete();

            return response()->json(['message' => 'Venta eliminada correctamente. Productos devueltos a lotes.'], 200);
        });
    }

    /**
     * Obtener ventas por usuario
     */
    public function sellsByUser($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $sells = Sell::where('id_usuario', $userId)
            ->with(['direction', 'details.product', 'details.detailLotes.lote'])
            ->get();

        return response()->json($sells, 200);
    }

    /**
     * Obtener ventas por estado
     */
    public function sellsByStatus($status)
    {
        $validStatus = ['Cancelado', 'Entregado', 'Pendiente'];

        if (!in_array($status, $validStatus)) {
            return response()->json(['message' => 'Estado inválido'], 400);
        }

        $sells = Sell::where('estado', $status)
            ->with(['user', 'direction', 'details.product', 'details.detailLotes.lote'])
            ->get();

        return response()->json($sells, 200);
    }

    /**
     * Actualizar estado de una venta
     */
    public function updateStatus(Request $request, $id)
    {
        $sell = Sell::find($id);

        if (!$sell) {
            return response()->json(['message' => 'Venta no encontrada'], 404);
        }

        $validated = $request->validate([
            'estado' => 'required|in:Cancelado,Entregado,Pendiente'
        ]);

        $sell->update(['estado' => $validated['estado']]);

        return response()->json($sell, 200);
    }

    /**
     * Obtener detalles de una venta con información de lotes
     */
    public function getDetailsSell($id)
    {
        $details = DetailSell::where('id_venta', $id)
            ->with(['product', 'detailLotes.lote'])
            ->get();

        if ($details->isEmpty()) {
            return response()->json(['message' => 'No hay detalles para esta venta'], 404);
        }

        return response()->json($details, 200);
    }
}


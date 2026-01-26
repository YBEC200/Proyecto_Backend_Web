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
use Illuminate\Support\Str;

class SellController extends Controller
{
    /**
     * Obtener todas las ventas con filtros opcionales
     * Query parameters:
     * - estado: Cancelado|Entregado|Pendiente
     * - nombre_cliente: búsqueda por nombre de usuario
     * - fecha: YYYY-MM-DD (búsqueda por fecha exacta)
     * - fecha_inicio: YYYY-MM-DD (rango de fechas)
     * - fecha_fin: YYYY-MM-DD (rango de fechas)
     */
    public function index(Request $request)
    {
        $query = Sell::with(['user:id,nombre,correo,rol,estado', 'direction:id,ciudad,calle,referencia', 'details.product:id,nombre,costo_unit', 'details.detailLotes.lote:Id,Lote,Fecha_Registro,Cantidad,Estado']);

        // Filtrar por estado
        if ($request->has('estado') && !empty($request->estado)) {
            $query->where('estado', $request->estado);
        }

        // Filtrar por nombre del cliente
        if ($request->has('nombre_cliente') && !empty($request->nombre_cliente)) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->nombre_cliente . '%')
                  ->orWhere('correo', 'like', '%' . $request->nombre_cliente . '%');
            });
        }

        // Filtrar por fecha exacta
        if ($request->has('fecha') && !empty($request->fecha)) {
            $query->whereDate('fecha', $request->fecha);
        }

        // Filtrar por rango de fechas
        if ($request->has('fecha_inicio') && !empty($request->fecha_inicio)) {
            $query->whereDate('fecha', '>=', $request->fecha_inicio);
        }
        if ($request->has('fecha_fin') && !empty($request->fecha_fin)) {
            $query->whereDate('fecha', '<=', $request->fecha_fin);
        }

        // Ordenar por fecha descendente (más recientes primero)
        $sells = $query->orderBy('fecha', 'desc')->get();

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
     * Crear una nueva venta
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'id_usuario' => 'required|exists:users,id',
                'fecha' => 'required|date',
                'metodo_pago' => 'required|in:Efectivo,Tarjeta,Deposito,Yape',
                'comprobante' => 'required|in:Boleta,Factura',
                'id_direccion' => 'nullable|exists:direccion,id',
                'tipo_entrega' => 'nullable|in:Envío,Recojo',
                'costo_total' => 'required|numeric|min:0',
                'estado' => 'required|in:Cancelado,Entregado,Pendiente',
                'details' => 'required|array|min:1',
                'details.*.id_producto' => 'required|exists:productos,id',
                'details.*.cantidad' => 'required|integer|min:1',
                'details.*.costo' => 'required|numeric|min:0'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);
        }

        return DB::transaction(function () use ($validated) {
            try {
                // Validar suficiente cantidad en lotes
                foreach ($validated['details'] as $detail) {
                    $product = Product::find($detail['id_producto']);
                    
                    if (!$product) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Producto no encontrado',
                            'code' => 'PRODUCT_NOT_FOUND'
                        ], 404);
                    }

                    // 🔍 CAMBIO AQUÍ: Estado es "Activo" no "Disponible"
                    $cantidadDisponible = Lote::where('Id_Producto', $detail['id_producto'])
                        ->where('Estado', 'Activo')
                        ->sum('Cantidad');

                    \Log::info('Stock Check', [
                        'producto_id' => $detail['id_producto'],
                        'producto_nombre' => $product->nombre,
                        'cantidad_solicitada' => $detail['cantidad'],
                        'cantidad_disponible' => $cantidadDisponible,
                    ]);

                    if ($cantidadDisponible < $detail['cantidad']) {
                        return response()->json([
                            'success' => false,
                            'message' => "Stock insuficiente",
                            'code' => 'INSUFFICIENT_STOCK',
                            'product_name' => $product->nombre,
                            'requested' => $detail['cantidad'],
                            'available' => $cantidadDisponible
                        ], 409);
                    }
                }

                // Validar dirección si es envío
                if ($validated['tipo_entrega'] === 'Envío' && !$validated['id_direccion']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Dirección requerida para envío a domicilio',
                        'code' => 'MISSING_ADDRESS'
                    ], 400);
                }

                $idDireccion = null;
                if ($validated['tipo_entrega'] === 'Envío') {
                    $idDireccion = $validated['id_direccion'] ?? null;
                }

                // Crear la venta
                $qrToken = null;

                if ($validated['tipo_entrega'] === 'Envío a Domicilio') {
                    $qrToken = Str::uuid(); // token único
                }

                // Crear la venta
                $sell = Sell::create([
                    'Id_Usuario'   => $validated['id_usuario'],
                    'Metodo_Pago'  => $validated['metodo_pago'],
                    'Comprobante'  => $validated['comprobante'],
                    'Id_Direccion' => $idDireccion,
                    'Fecha'        => $validated['fecha'],
                    'Costo_Total'  => $validated['costo_total'],
                    'Estado'       => $validated['estado'],
                    'tipo_entrega' => $validated['tipo_entrega'],
                    'qr_token'     => $qrToken
                ]);

                // Crear detalles de venta y procesar lotes
                foreach ($validated['details'] as $detail) {
                    $detailSell = DetailSell::create([
                        'Id_Venta' => $sell->Id,  // Cambio a mayúsculas
                        'Id_Producto' => $detail['id_producto'],  
                        'Cantidad' => $detail['cantidad'],  
                        'Costo' => $detail['costo']  
                    ]);

                    $cantidadFaltante = $detail['cantidad'];
                    $lotes = Lote::where('Id_Producto', $detail['id_producto'])
                        ->where('Estado', 'Activo')
                        ->orderBy('Fecha_Registro', 'asc')
                        ->get();

                    foreach ($lotes as $lote) {
                        if ($cantidadFaltante <= 0) {
                            break;
                        }

                        $cantidadADescontar = min($cantidadFaltante, $lote->Cantidad);

                        DetailLote::create([
                            'Id_Detalle_Venta' => $detailSell->Id,  // ✅ Cambiar a mayúsculas
                            'Id_Lote' => $lote->Id,  // ✅ Cambiar a mayúsculas
                            'Cantidad' => $cantidadADescontar  // ✅ Cambiar a mayúsculas
                        ]);

                        $lote->Cantidad -= $cantidadADescontar;
                        
                        if ($lote->Cantidad <= 0) {
                            $lote->Estado = 'Agotado';
                        }
                        
                        $lote->save();
                        $cantidadFaltante -= $cantidadADescontar;
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Venta creada correctamente',
                    'qr_token' => $sell->qr_token,
                    'data' => $sell->load(['user', 'direction', 'details.product', 'details.detailLotes.lote'])
                ], 201);

            } catch (\Exception $e) {
                // 🔍 LOG DETALLADO DEL ERROR
                \Log::error('Error creando venta', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);

                DB::rollBack();
                
                return response()->json([
                    'success' => false,
                    'message' => 'Error al procesar la venta. Por favor, contacte al administrador.',
                    'code' => 'PROCESSING_ERROR'
                ], 500);
            }
        });
    }

    /**
     * Formatear errores de validación de forma amigable
     */
    private function formatValidationErrors($errors)
    {
        $formatted = [];
        
        foreach ($errors as $field => $messages) {
            if (strpos($field, 'details') !== false) {
                $formatted['products'] = 'Revise los productos seleccionados';
            } elseif ($field === 'id_usuario') {
                $formatted[$field] = 'Usuario inválido';
            } elseif ($field === 'metodo_pago') {
                $formatted[$field] = 'Método de pago inválido';
            } elseif ($field === 'comprobante') {
                $formatted[$field] = 'Comprobante inválido';
            } elseif ($field === 'costo_total') {
                $formatted[$field] = 'Costo inválido';
            } else {
                $formatted[$field] = 'Campo inválido';
            }
        }
        
        return $formatted;
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
            'id_direccion' => 'sometimes|required|exists:direccion,id',
            'costo_total' => 'sometimes|required|numeric|min:0',
            'estado' => 'sometimes|required|in:Cancelado,Entregado,Pendiente'
        ]);

        $mappedData = [
            'Id_Usuario' => $validated['id_usuario'] ?? $sell->Id_Usuario,
            'Metodo_Pago' => $validated['metodo_pago'] ?? $sell->Metodo_Pago,
            'Comprobante' => $validated['comprobante'] ?? $sell->Comprobante,
            'Id_Direccion' => $validated['id_direccion'] ?? $sell->Id_Direccion,
            'Costo_Total' => $validated['costo_total'] ?? $sell->Costo_Total,
            'Estado' => $validated['estado'] ?? $sell->Estado
        ];

        $sell->update($mappedData);

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
                $query->where('Id_Venta', $id);
            })->get();

            // Devolver cantidad a los lotes
            foreach ($detailLotes as $detailLote) {
                $lote = Lote::find($detailLote->Id_Lote);
                if ($lote) {
                    $lote->Cantidad += $detailLote->Cantidad;
                    
                    // Si estaba agotado y ahora tiene cantidad, cambiar a Disponible
                    if ($lote->Estado === 'Agotado' && $lote->Cantidad > 0) {
                        $lote->Estado = 'Disponible';
                    }
                    
                    $lote->save();
                }
            }

            // Eliminar detalles de lote
            DetailLote::whereHas('detailSell', function ($query) use ($id) {
                $query->where('Id_Venta', $id);
            })->delete();

            // Eliminar detalles de venta
            DetailSell::where('Id_Venta', $id)->delete();

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

        $sells = Sell::where('Id_Usuario', $userId)
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
        $details = DetailSell::where('Id_Venta', $id)
            ->with(['product', 'detailLotes.lote'])
            ->get();

        if ($details->isEmpty()) {
            return response()->json(['message' => 'No hay detalles para esta venta'], 404);
        }

        return response()->json($details, 200);
    }
}


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
use App\Services\NubeFactService;


class SellController extends Controller
{
    /**
     * Obtener todas las ventas con filtros opcionales
     * Query parameters:
     * - estado: Cancelado|Entregado|Pendiente (El estado 'En Revision' no se incluye en esta lista, para eso hay un endpoint específico)
     * - nombre_cliente: búsqueda por nombre de usuario
     * - fecha: YYYY-MM-DD (búsqueda por fecha exacta)
     * - fecha_inicio: YYYY-MM-DD (rango de fechas)
     * - fecha_fin: YYYY-MM-DD (rango de fechas)
     * - no se deben incluir los que tienen estado 'En Revision'
     */
    public function index(Request $request)
    {
        $query = Sell::with(['user:id,nombre,correo,rol,estado', 'direction:id,ciudad,calle,referencia', 'details.product:id,nombre,costo_unit', 'details.detailLotes.lote:Id,Lote,Fecha_Registro,Cantidad,Estado']);

        // Filtrar por estado 'En Revision'
        $query->where('estado', '!=', 'En Revision');

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
     * Obtener ventas todas las ventas con Estado 'En Revision'(VENDRAN DEL APARTADO MOVIL)
     */
    public function ventasEnRevision()
    {
        $sells = Sell::with(['user:id,nombre,correo,rol,estado', 'direction:id,ciudad,calle,referencia', 'details.product:id,nombre,costo_unit', 'details.detailLotes.lote:Id,Lote,Fecha_Registro,Cantidad,Estado'])
            ->where('estado', 'En Revision')
            ->orderBy('fecha', 'desc')
            ->get();

        return response()->json($sells, 200);
    }

    /**
     * Actualizar estado de venta de 'En Revision' a 'Pendiente', ya que aun no ah sudo enviada a domicilio ni recogida en tienda
     */
    public function aprobarVenta($id)
    {
        $nubeFact = app(NubeFactService::class);

        $sell = Sell::with([
            'details.product',
            'user',
            'direction'
        ])->find($id);

        if (!$sell) {
            return response()->json([
                'success' => false,
                'message' => 'Venta no encontrada'
            ], 404);
        }

        if ($sell->estado !== 'En Revision') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden aprobar ventas en estado En Revision'
            ], 400);
        }

        if ($sell->codigo_unico) {
            return response()->json([
                'success' => false,
                'message' => 'La venta ya tiene comprobante emitido'
            ], 400);
        }

        DB::beginTransaction();

        try {

            /* =========================
            1️⃣ PREPARAR DATOS
            ========================= */

            $codigoUnico = 'VENTA-' . $sell->getKey();

            // 🔴 CARGA CLAVE (igual que en store)
            $sell->load(['details.product']);

            $payload = $this->buildNubeFactPayload($sell);

            \Log::info('Payload enviado a NubeFact (APROBAR VENTA)', [
                'venta_id' => $sell->Id,
                'payload' => $payload
            ]);

            /* =========================
            2️⃣ EMITIR COMPROBANTE
            ========================= */

            $respuesta = $nubeFact->emitirComprobante($payload);

            \Log::info('Respuesta Nubefact (APROBAR VENTA)', [
                'venta_id' => $sell->Id,
                'respuesta' => $respuesta
            ]);

            /* =========================
            3️⃣ GUARDAR DATOS NUBEFACT
            ========================= */

            $sell->update([
                'codigo_unico'       => $codigoUnico,
                'serie'              => $respuesta['data']['serie']
                                        ?? $respuesta['serie']
                                        ?? null,
                'numero_comprobante' => $respuesta['data']['numero']
                                        ?? $respuesta['numero']
                                        ?? null,
                'enlace_pdf'         => $respuesta['data']['enlace_del_pdf']
                                        ?? $respuesta['enlace_del_pdf']
                                        ?? null,
                'nubefact_key'       => $respuesta['data']['key']
                                        ?? $respuesta['key']
                                        ?? null,
                'estado'             => 'Pendiente'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Venta aprobada y boleta generada correctamente',
                'data' => $sell->fresh()->load([
                    'user',
                    'direction',
                    'details.product',
                    'details.detailLotes.lote'
                ])
            ], 200);

        } catch (\Throwable $e) {

            DB::rollBack();

            \Log::error('Error aprobando venta y emitiendo boleta', [
                'venta_id' => $sell->Id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar venta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar venta en estado "En Revision"
     * Devuelve productos a lotes y cambia estado a "Cancelado"
     */
    public function cancelarVentaEnRevision(Request $request, $id)
    {
        $sell = Sell::with([
            'details.detailLotes'
        ])->find($id);

        if (!$sell) {
            return response()->json([
                'success' => false,
                'message' => 'Venta no encontrada'
            ], 404);
        }

        if ($sell->estado !== 'En Revision') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden cancelar ventas en estado En Revision'
            ], 400);
        }

        return DB::transaction(function () use ($sell, $request) {

            /* =========================
            1️⃣ DEVOLVER STOCK A LOTES
            ========================= */

            foreach ($sell->details as $detail) {
                foreach ($detail->detailLotes as $detailLote) {

                    $lote = Lote::find($detailLote->Id_Lote);

                    if ($lote) {
                        $lote->Cantidad += $detailLote->Cantidad;

                        if ($lote->Estado === 'Agotado' && $lote->Cantidad > 0) {
                            $lote->Estado = 'Disponible';
                        }

                        $lote->save();
                    }
                }
            }

            /* =========================
            2️⃣ ACTUALIZAR ESTADO
            ========================= */

            $sell->update([
                'estado' => 'Cancelado',
                'motivo_cancelacion' => $request->motivo ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Venta cancelada correctamente. Stock devuelto.',
                'data' => $sell->fresh()->load([
                    'user',
                    'details.product',
                    'details.detailLotes.lote'
                ])
            ], 200);
        });
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
        $nubeFact = app(NubeFactService::class);

        try {
            $validated = $request->validate([
                'id_usuario' => 'required|exists:users,id',
                'fecha' => 'required|date',
                'metodo_pago' => 'required|in:Efectivo,Tarjeta,Deposito,Yape',
                'comprobante' => 'required|in:Boleta,Factura',
                'ruc' => 'nullable|string|size:11',
                'id_direccion' => 'nullable|exists:direccion,Id',
                'tipo_entrega' => 'required|in:Envío a Domicilio,Recojo en Tienda',
                'costo_total' => 'required|numeric|min:0',
                'details' => 'required|array|min:1',
                'details.*.id_producto' => 'required|exists:productos,id',
                'details.*.cantidad' => 'required|integer|min:1',
                'details.*.costo' => 'required|numeric|min:0'
            ]);

            /* 🔴 VALIDACIÓN CRÍTICA */
            if (
                ($validated['comprobante'] ?? null) === 'Factura' &&
                empty($validated['ruc'])
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'El RUC es obligatorio para emitir factura'
                ], 422);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);
        }

        return DB::transaction(function () use ($validated, $nubeFact) {

            /* =========================
            1️⃣ VALIDAR STOCK
            ========================= */
            foreach ($validated['details'] as $detail) {

                $cantidadDisponible = Lote::where('Id_Producto', $detail['id_producto'])
                    ->where('Estado', 'Activo')
                    ->sum('Cantidad');

                if ($cantidadDisponible < $detail['cantidad']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Stock insuficiente',
                        'producto_id' => $detail['id_producto'],
                        'requested' => $detail['cantidad'],
                        'available' => $cantidadDisponible
                    ], 409);
                }
            }

            /* =========================
            2️⃣ VALIDAR DIRECCIÓN
            ========================= */
            if (
                $validated['tipo_entrega'] === 'Envío a Domicilio' &&
                empty($validated['id_direccion'])
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dirección requerida para envío'
                ], 400);
            }

            /* =========================
            3️⃣ CREAR VENTA
            ========================= */
            $sell = Sell::create([
                'Id_Usuario'   => $validated['id_usuario'],
                'Metodo_Pago'  => $validated['metodo_pago'],
                'Comprobante'  => $validated['comprobante'],
                'RUC'          => $validated['ruc'] ?? null,
                'Id_Direccion' => $validated['id_direccion'] ?? null,
                'Fecha'        => $validated['fecha'],
                'Costo_Total'  => $validated['costo_total'],
                'estado'       => $validated['tipo_entrega'] === 'Recojo en Tienda'
                                    ? 'Entregado'
                                    : 'Pendiente',
                'tipo_entrega' => $validated['tipo_entrega'],
                'qr_token'     => $validated['tipo_entrega'] === 'Envío a Domicilio'
                                    ? \Str::uuid()
                                    : null,
            ]);
            /* =========================
            4️⃣ DETALLES + LOTES
            ========================= */
            foreach ($validated['details'] as $detail) {

                $detailSell = DetailSell::create([
                    'Id_Venta'    => $sell->Id,
                    'Id_Producto' => $detail['id_producto'],
                    'Cantidad'    => $detail['cantidad'],
                    'Costo'       => $detail['costo']
                ]);

                $cantidadFaltante = $detail['cantidad'];

                $lotes = Lote::where('Id_Producto', $detail['id_producto'])
                    ->where('Estado', 'Activo')
                    ->orderBy('Fecha_Registro')
                    ->get();

                foreach ($lotes as $lote) {

                    if ($cantidadFaltante <= 0) break;

                    $descontar = min($cantidadFaltante, $lote->Cantidad);

                    DetailLote::create([
                        'Id_Detalle_Venta' => $detailSell->Id,
                        'Id_Lote' => $lote->Id,
                        'Cantidad' => $descontar
                    ]);

                    $lote->Cantidad -= $descontar;
                    if ($lote->Cantidad <= 0) {
                        $lote->Estado = 'Inactivo';
                    }
                    $lote->save();

                    $cantidadFaltante -= $descontar;
                }
            }

            /* =========================
            5️⃣ EMITIR COMPROBANTE (NUBEFACT)
            ========================= */
            $sell->refresh();
            $codigoUnico = 'VENTA-' . $sell->getKey();
            try {

                // 🔴 CLAVE
                $sell->load(['details.product']);

                $payload = $this->buildNubeFactPayload($sell);

                \Log::info('Payload enviado a NubeFact', $payload);

                $respuesta = $nubeFact->emitirComprobante($payload);
                \Log::info('RESPUESTA NUBEFACT', $respuesta);

                $sell->update([
                    'codigo_unico'       => $codigoUnico,
                    'serie'              => $respuesta['data']['serie'] ?? $respuesta['serie'] ?? null,
                    'numero_comprobante' => $respuesta['data']['numero'] ?? $respuesta['numero'] ?? null,
                    'enlace_pdf'         => $respuesta['data']['enlace_del_pdf'] ?? $respuesta['enlace_del_pdf'] ?? null,
                    'nubefact_key'       => $respuesta['data']['key'] ?? $respuesta['key'] ?? null,
                ]);

            } catch (\Exception $e) {

                \Log::error('Error al emitir comprobante NubeFact', [
                    'venta_id' => $sell->Id,
                    'error' => $e->getMessage()
                ]);
            }


            /* =========================
            6️⃣ RESPUESTA FINAL
            ========================= */
            return response()->json([
                'success' => true,
                'message' => 'Venta creada correctamente',
                'qr_token' => $sell->qr_token,
                'data' => $sell->load([
                    'user',
                    'direction',
                    'details.product',
                    'details.detailLotes.lote'
                ])
            ], 201);
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

    /**
     * Cancelar una venta y reabastecimiento de lotes
     * Revierte los desuentos de stock que se hicieron al crear la venta
     */
    public function cancelSell(Request $request, $id)
    {
        $sell = Sell::find($id);

        if (!$sell) {
            return response()->json([
                'success' => false,
                'message' => 'Venta no encontrada',
                'code' => 'SALE_NOT_FOUND'
            ], 404);
        }

        if ($sell->estado === 'Cancelado') {
            return response()->json([
                'success' => false,
                'message' => 'Esta venta ya fue cancelada',
                'code' => 'ALREADY_CANCELLED'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // 🔹 Obtener todos los detalles de lote asociados a la venta
            $detailLotes = DetailLote::whereHas('detailSell', function ($query) use ($id) {
                $query->where('Id_Venta', $id);
            })->with('lote')->get();

            // 🔹 Reabastecer los lotes
            foreach ($detailLotes as $detailLote) {

                $lote = $detailLote->lote;

                if (!$lote) {
                    continue;
                }

                $lote->Cantidad += $detailLote->Cantidad;

                if ($lote->Estado === 'Agotado' && $lote->Cantidad > 0) {
                    $lote->Estado = 'Activo';
                }

                $lote->save();

                \Log::info('Lote reabastecido por cancelación', [
                    'Id_Lote' => $lote->Id,
                    'Cantidad_Devuelta' => $detailLote->Cantidad,
                    'Cantidad_Total' => $lote->Cantidad,
                    'Estado_Lote' => $lote->Estado,
                    'Id_Venta' => $id
                ]);
            }

            // 🔹 Cancelar la venta
            $sell->estado = 'Cancelado';
            $sell->save();

            DB::commit();

            \Log::info('Venta cancelada correctamente', [
                'Id_Venta' => $sell->Id,
                'Id_Usuario' => $sell->Id_Usuario,
                'Costo_Total' => $sell->Costo_Total,
                'Lotes_Procesados' => $detailLotes->count()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Venta cancelada y productos reabastecidos correctamente',
                'code' => 'SALE_CANCELLED',
                'venta_id' => $sell->Id,
                'estado' => $sell->estado,
                'lotes_procesados' => $detailLotes->count(),
                'data' => $sell->load([
                    'user',
                    'direction',
                    'details.product',
                    'details.detailLotes.lote'
                ])
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            \Log::error('Error al cancelar venta', [
                'Id_Venta' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar la venta. Contacte al administrador.',
                'code' => 'CANCELLATION_ERROR'
            ], 500);
        }
    }

    private function buildNubeFactPayload(Sell $sell)
    {
        $tipoComprobante = $sell->Comprobante === 'Factura' ? 1 : 2;
        $serie = $tipoComprobante === 1 ? 'F001' : 'B001';

        $totalGravada = 0;
        $totalIgv = 0;
        $total = 0;

        $items = [];

        foreach ($sell->details as $detail) {

            // 🔴 DATOS REALES DE TU MODELO
            $cantidad = (int) $detail->Cantidad;
            $precioUnitario = (float) $detail->Costo; // con IGV

            // 🔴 CÁLCULO INDEPENDIENTE (SOLO PARA SUNAT)
            $valorUnitario = round($precioUnitario / 1.18, 2);
            $subtotal = round($valorUnitario * $cantidad, 2);
            $igv = round($subtotal * 0.18, 2);
            $totalLinea = round($subtotal + $igv, 2);

            $totalGravada += $subtotal;
            $totalIgv += $igv;
            $total += $totalLinea;

            $items[] = [
                "unidad_de_medida" => "NIU",
                "codigo" => $detail->product->id,
                "descripcion" => $detail->product->nombre,
                "cantidad" => $cantidad,
                "valor_unitario" => $valorUnitario,
                "precio_unitario" => $precioUnitario,
                "subtotal" => $subtotal,
                "tipo_de_igv" => 1,
                "igv" => $igv,
                "total" => $totalLinea
            ];
        }

        return [
            "operacion" => "generar_comprobante",
            "tipo_de_comprobante" => $tipoComprobante,
            "serie" => $serie,
            "numero" => "",
            "codigo_unico" => 'VENTA-' . $sell->Id,
            "cliente_tipo_de_documento" => $tipoComprobante === 1 ? "6" : "1",
            "cliente_numero_de_documento" => $sell->RUC ?? "12345678",
            "cliente_denominacion" => $tipoComprobante === 1
                ? "CLIENTE FACTURA"
                : "CLIENTE BOLETA",
            "fecha_de_emision" => now('America/Lima')->format('d-m-Y'),
            "moneda" => 1,
            "total_gravada" => round($totalGravada, 2),
            "total_igv" => round($totalIgv, 2),
            "total" => round($total, 2),
            "items" => $items
        ];

    }
}


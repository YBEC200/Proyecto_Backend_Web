<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sell;
use App\Models\DetailSell;
use App\Models\DetailLote;
use App\Models\Lote;
use Illuminate\Support\Facades\DB;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Str;

class MovilSellController extends Controller
{
    /**
     * Crear una nueva venta por parte de la app móvil. Este endpoint se encargará de:
     * 1️⃣ Validar el stock disponible para cada producto solicitado.
     * 2️⃣ Validar que si el tipo de entrega es "Envío a Domicilio", se proporcione una dirección válida.
     * 3️⃣ Crear la venta y sus detalles.
     * 4️⃣ Descontar el stock de los lotes correspondientes (FIFO).
     * 6️⃣ Capturar una imagen del comprobante y almacenarla en Cloudinary, guardando la URL en la base de datos.(usa las variavbles de entorno CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, CLOUDINARY_API_SECRET)
     * 7️⃣ Considera que todas estan ventas tendran el estado de 'En revision', el qr_token se generara en todos ya que en este caso el qr es el valdiardor de que la venta a este usuario se haya realizado correctamente.
     * 8️⃣ Finalmente lo mas importante esta venta aun no ah generado una boleta ay que esta en revision, por lo tanto el endpoint no emitira el comprobante en NubeFact, solo se limitara a crear la venta en la db y devolver los datos de esta. 
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_usuario' => 'required|exists:users,id',
            'fecha' => 'required|date',
            'comprobante' => 'required|in:Boleta,Factura',
            'ruc' => 'required_if:comprobante,Factura|nullable|string|size:11',
            'id_direccion' => 'nullable|exists:direccion,Id',
            'tipo_entrega' => 'required|in:Envío a Domicilio,Recojo en Tienda',
            'details' => 'required|array|min:1',
            'details.*.id_producto' => 'required|exists:productos,id',
            'details.*.cantidad' => 'required|integer|min:1',
            'details.*.costo' => 'required|numeric|min:0',
            'voucher' => 'required|image|max:4096'
        ]);

        try {

            return DB::transaction(function () use ($validated, $request) {

                /* =========================
                1️⃣ VALIDAR DIRECCIÓN
                ========================= */
                if (
                    $validated['tipo_entrega'] === 'Envío a Domicilio' &&
                    empty($validated['id_direccion'])
                ) {
                    throw new \Exception('Dirección requerida para envío');
                }

                /* =========================
                2️⃣ VALIDAR STOCK + CALCULAR TOTAL
                ========================= */
                $totalCalculado = 0;

                foreach ($validated['details'] as $detail) {

                    $lotes = Lote::where('Id_Producto', $detail['id_producto'])
                        ->where('Estado', 'Activo')
                        ->lockForUpdate()
                        ->get();

                    $cantidadDisponible = $lotes->sum('Cantidad');

                    if ($cantidadDisponible < $detail['cantidad']) {
                        throw new \Exception("Stock insuficiente para producto {$detail['id_producto']}");
                    }

                    $totalCalculado += $detail['cantidad'] * $detail['costo'];
                }

                /* =========================
                3️⃣ SUBIR VOUCHER A CLOUDINARY
                ========================= */
                $voucherUrl = null;

                if ($request->hasFile('voucher')) {
                    $upload = Cloudinary::upload(
                        $request->file('voucher')->getRealPath(),
                        [
                            'folder' => 'vouchers'
                        ]
                    );
                    $voucherUrl = $upload->getSecurePath();
                }

                /* =========================
                4️⃣ CREAR VENTA
                ========================= */
                $sell = Sell::create([
                    'Id_Usuario' => $validated['id_usuario'],
                    'Metodo_Pago' => 'Yape', // 🔥 Forzado automáticamente
                    'Comprobante' => $validated['comprobante'],
                    'Ruc' => $validated['ruc'] ?? null,
                    'Id_Direccion' => $validated['id_direccion'] ?? null,
                    'Fecha' => $validated['fecha'],
                    'Costo_Total' => $totalCalculado,
                    'estado' => 'En Revision',
                    'tipo_entrega' => $validated['tipo_entrega'],
                    'qr_token' => Str::uuid(),
                    'voucher_url' => $voucherUrl,
                ]);

                /* =========================
                5️⃣ CREAR DETALLES + DESCONTAR FIFO
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
                        ->lockForUpdate()
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
                6️⃣ RESPUESTA FINAL
                ========================= */
                return response()->json([
                    'success' => true,
                    'message' => 'Venta creada y enviada a revisión',
                    'qr_token' => $sell->qr_token,
                    'data' => $sell->load([
                        'user',
                        'direction',
                        'details.product',
                        'details.detailLotes.lote'
                    ])
                ], 201);

            });

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /*
    * Mostrar ventas de un usuario específico, para el apartado movil "Mis Compras"
     */
    public function show(Request $request)
    {
        $validated = $request->validate([
            'fecha' => 'nullable|date_format:Y-m-d',
            'fecha_desde' => 'nullable|date_format:Y-m-d',
            'fecha_hasta' => 'nullable|date_format:Y-m-d|after_or_equal:fecha_desde',

            'estado' => [
                'nullable',
                'in:Cancelado,Entregado,Pendiente,En Revision'
            ],

            'tipo_entrega' => [
                'nullable',
                'in:Envío a Domicilio,Recojo en Tienda'
            ],

            'comprobante' => [
                'nullable',
                'in:Boleta,Factura'
            ],
        ]);

        $ventas = Sell::query()
            ->where('Id_Usuario', $request->user()->id)
            ->when(
                $validated['fecha'] ?? null,
                fn ($query, $fecha) => $query->whereDate('Fecha', $fecha)
            )
            ->when(
                $validated['fecha_desde'] ?? null,
                fn ($query, $fechaDesde) => $query->whereDate('Fecha', '>=', $fechaDesde)
            )
            ->when(
                $validated['fecha_hasta'] ?? null,
                fn ($query, $fechaHasta) => $query->whereDate('Fecha', '<=', $fechaHasta)
            )
            ->when(
                $validated['estado'] ?? null,
                fn ($query, $estado) => $query->where('estado', $estado)
            )
            ->when(
                $validated['tipo_entrega'] ?? null,
                fn ($query, $tipoEntrega) => $query->where('tipo_entrega', $tipoEntrega)
            )
            ->when(
                $validated['comprobante'] ?? null,
                fn ($query, $comprobante) => $query->where('Comprobante', $comprobante)
            )
            ->with([
                'details.product',
                'direction'
            ])
            ->orderByDesc('Fecha')
            ->get();

        return response()->json([
            'success' => true,
            'total' => $ventas->count(),
            'data' => $ventas
        ]);
    }

    /**
     * Mostrar detalles de una venta específica de un usuario
     */
    public function detallesVenta(Request $request, $id)
    {
        $venta = Sell::select([
                'Id',
                'Id_Usuario',
                'Metodo_Pago',
                'Comprobante',
                'Ruc',
                'Id_Direccion',
                'Fecha',
                'Costo_Total',
                'estado',
                'tipo_entrega',
                'voucher_url',
                'qr_token',
                'codigo_unico',
                'serie',
                'numero_comprobante',
                'enlace_pdf',
                'motivo_cancelacion',
            ])
            ->where('Id', $id)
            ->where('Id_Usuario', $request->user()->id)
            ->with([
                'user:id,nombre,correo',
                'details.product:id,nombre,descripcion,marca',
                'direction:Id,ciudad,calle,referencia',
            ])
            ->first();

        if (!$venta) {
            return response()->json([
                'success' => false,
                'message' => 'Venta no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $venta
        ], 200);
    }

    /**
     * Validar entrega mediante QR
     */
    public function validarEntregaPorQR(Request $request)
    {
        $validated = $request->validate([
            'qr_token' => 'required|string'
        ]);

        // Buscar venta por el token
        $sell = Sell::where('qr_token', $validated['qr_token'])->first();

        if (!$sell) {
            return response()->json([
                'message' => 'Código QR inválido o no existe'
            ], 404);
        }

        // Verificar estado actual
        if ($sell->estado === 'Entregado') {
            return response()->json([
                'message' => 'Esta venta ya fue entregada'
            ], 400);
        }

        if ($sell->estado === 'Cancelado') {
            return response()->json([
                'message' => 'Esta venta fue cancelada y no puede ser entregada'
            ], 400);
        }

        if ($sell->estado === 'En Revision') {
            return response()->json([
                'message' => 'Esta venta aún está en revisión y no puede ser entregada'
            ], 400);
        }

        // Actualizar estado a Entregado
        $sell->estado = 'Entregado';
        $sell->save();

        return response()->json([
            'message' => 'Entrega confirmada correctamente',
            'venta_id' => $sell->id,
            'estado' => $sell->estado,
            'fecha_entrega' => now()
        ], 200);
    }

}

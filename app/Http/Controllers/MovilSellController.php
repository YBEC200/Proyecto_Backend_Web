<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sell;
use App\Models\DetailSell;
use App\Models\DetailLote;
use App\Models\Lote;
use Illuminate\Support\Facades\DB;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

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
        // Validación de datos
        $validated = $request->validate([
            'id_usuario' => 'required|exists:users,id',
            'fecha' => 'required|date',
            'metodo_pago' => 'required|in:Efectivo,Tarjeta,Deposito,Yape',
            'comprobante' => 'required|in:Boleta,Factura',
            'ruc' => 'nullable|string|size:11',
            'id_direccion' => 'nullable|exists:direccion,id',
            'tipo_entrega' => 'required|in:Envío a Domicilio,Recojo en Tienda',
            'costo_total' => 'required|numeric|min:0',
            'details' => 'required|array|min:1',
            'details.*.id_producto' => 'required|exists:productos,id',
            'details.*.cantidad' => 'required|integer|min:1',
            'details.*.costo' => 'required|numeric|min:0'
        ]);

        // Aquí iría la lógica para crear la venta, validar stock, emitir comprobante, etc.
        // Por simplicidad, solo devolveremos los datos validados por ahora.

        return DB::transaction(function () use ($validated) {
            // Validar stock, crear venta, emitir comprobante, etc.
            // Este es un ejemplo simplificado y no implementa toda la lógica mencionada.

            /* =========================
            1️⃣ VALIDAR STOCK
            ========================= */
            foreach ($validated['details'] as $detail) {

                $cantidadDisponible = Lote::where('Id_Producto', $detail['id_producto'])
                            ->where('Estado', 'Activo')
                            ->lockForUpdate()
                            ->get();;

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

            $sell = Sell::create([
                'Id_Usuario' => $validated['id_usuario'],
                'Metodo_Pago' => $validated['metodo_pago'],
                'Comprobante' => $validated['comprobante'],
                'RUC' => $validated['ruc'] ?? null,
                'Id_Direccion' => $validated['id_direccion'] ?? null,
                'Fecha' => $validated['fecha'],
                'Costo_Total' => $validated['costo_total'],
                'estado' => 'En Revision',
                'tipo_entrega' => $validated['tipo_entrega'],
                'qr_token' => \Str::uuid(),
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

    /*
    * Mostrar ventas de un usuario específico, para el apartado movil "Mis Compras"
     */
    public function show($id){
        $ventas = Sell::where('Id_Usuario', $id)
            ->with(['details.product', 'direction'])
            ->orderBy('Fecha', 'desc')
            ->get();

        if (request()->has('estado')) {
            $estado = request()->query('estado');
            $ventas = $ventas->where('estado', $estado);
        }

        return response()->json($ventas);
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

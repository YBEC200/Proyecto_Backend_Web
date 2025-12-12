<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Sell;
use App\Models\DetailSell;
use App\Models\DetailLote;
use App\Models\Product;
use App\Models\Lote;

class SellController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'Id_Usuario' => 'required|integer|exists:users,id',
            'Id_Metodo_Pago' => 'nullable|integer',
            'Id_Comprobante' => 'nullable|integer',
            'Id_Direccion' => 'nullable|integer',
            'Fecha' => 'nullable|date',
            'Costo_total' => 'required|numeric|min:0',
            'estado' => 'nullable|string',
            'detalles' => 'required|array|min:1',
            'detalles.*.Id_Producto' => 'required|integer|exists:productos,id',
            'detalles.*.Cantidad' => 'required|integer|min:1',
            'detalles.*.Costo' => 'required|numeric|min:0',
            'detalles.*.lotes' => 'nullable|array',
            'detalles.*.lotes.*.Id_Lote' => 'required_with:detalles.*.lotes|integer|exists:lote,Id',
            'detalles.*.lotes.*.Cantidad' => 'required_with:detalles.*.lotes|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $ventaData = [
                'Id_Usuario' => $request->input('Id_Usuario'),
                'Id_Metodo_Pago' => $request->input('Id_Metodo_Pago', 1),  // ← CAMBIO: 1 por defecto
                'Id_Comprobante' => $request->input('Id_Comprobante', 1),
                'Id_Direccion' => $request->input('Id_Direccion', null),
                'Fecha' => $request->input('Fecha') ?? now()->toDateString(),
                'Costo_total' => $request->input('Costo_total'),
                'estado' => $request->input('estado', 'pendiente'),
            ];

            if ($request->filled('Id_Metodo_Pago')) {
                $ventaData['Id_Metodo_Pago'] = $request->input('Id_Metodo_Pago');
            }
            if ($request->filled('Id_Comprobante')) {
                $ventaData['Id_Comprobante'] = $request->input('Id_Comprobante');
            }
            if ($request->filled('Id_Direccion')) {
                $ventaData['Id_Direccion'] = $request->input('Id_Direccion');
            }

            $venta = Sell::create($ventaData);
            $ventaId = $venta->getKey();

            foreach ($request->input('detalles', []) as $detalle) {
                $producto = Product::find($detalle['Id_Producto']);
                if (!$producto) {
                    throw new \Exception("Producto no encontrado: {$detalle['Id_Producto']}");
                }

                $cantidadRequerida = intval($detalle['Cantidad']);

                $detalleVenta = DetailSell::create([
                    'Id_Venta' => $ventaId,
                    'Id_Producto' => $detalle['Id_Producto'],
                    'Cantidad' => $cantidadRequerida,
                    'Costo' => floatval($detalle['Costo']),
                ]);

                if (!empty($detalle['lotes']) && is_array($detalle['lotes'])) {
                    $totalAsignado = 0;
                    foreach ($detalle['lotes'] as $asig) {
                        $lote = Lote::find($asig['Id_Lote']);
                        if (!$lote) {
                            throw new \Exception("Lote no encontrado: {$asig['Id_Lote']}");
                        }
                        $qty = intval($asig['Cantidad']);
                        if ($qty <= 0) throw new \Exception("Cantidad inválida en lote {$asig['Id_Lote']}");
                        if ($lote->Cantidad < $qty) throw new \Exception("Stock insuficiente en lote {$asig['Id_Lote']}");

                        DetailLote::create([
                            'Id_Detalle_Venta' => $detalleVenta->getKey(),
                            'Id_Lote' => $lote->getKey(),
                            'Cantidad' => $qty,
                        ]);

                        $lote->Cantidad -= $qty;
                        $lote->save();

                        $totalAsignado += $qty;
                    }
                    if ($totalAsignado < $cantidadRequerida) {
                        throw new \Exception("No se asignaron suficientes unidades desde lotes para el producto {$detalle['Id_Producto']}");
                    }
                } else {
                    $this->asignarLotesAutomaticamente($detalleVenta, $detalle['Id_Producto'], $cantidadRequerida);
                }
            }

            DB::commit();

            $venta = $venta->load('detalles');
            return response()->json(['message' => 'Venta creada exitosamente', 'venta' => $venta], 201);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            DB::rollBack();
            return response()->json(['message' => 'Error de validación', 'errors' => $ve->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('SellController@store error: '.$e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => $e->getMessage() ?: 'Error al crear la venta'], 500);
        }
    }

    private function asignarLotesAutomaticamente($detalleVenta, $productoId, $cantidadRequerida)
    {
        $lotes = Lote::where('Id_Producto', $productoId)
            ->where('Cantidad', '>', 0)
            ->orderBy('Fecha_Registro', 'asc')
            ->get();

        if ($lotes->isEmpty()) {
            throw new \Exception("No hay stock disponible para el producto {$productoId}. Se requieren {$cantidadRequerida} unidades.");
        }

        $cantidadAsignada = 0;

        foreach ($lotes as $lote) {
            if ($cantidadAsignada >= $cantidadRequerida) break;

            $cantidadDisponible = (int)$lote->Cantidad;
            $cantidadAAsignar = min($cantidadDisponible, $cantidadRequerida - $cantidadAsignada);

            DetailLote::create([
                'Id_Detalle_Venta' => $detalleVenta->getKey(),
                'Id_Lote' => $lote->getKey(),
                'Cantidad' => $cantidadAAsignar,
            ]);

            $lote->Cantidad -= $cantidadAAsignar;
            $lote->save();

            $cantidadAsignada += $cantidadAAsignar;
        }

        if ($cantidadAsignada < $cantidadRequerida) {
            throw new \Exception("Stock insuficiente. Se requieren {$cantidadRequerida} unidades pero solo hay {$cantidadAsignada} disponibles.");
        }
    }
}
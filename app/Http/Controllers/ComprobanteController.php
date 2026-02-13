<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sell;
use App\Services\NubeFactService;

class ComprobanteController extends Controller
{
    /**
     * Listar boletas emitidas
     */
    public function boletas()
    {
        $boletas = Sell::where('Comprobante', 'Boleta')
            ->whereNotNull('serie')
            ->whereNotNull('numero_comprobante')
            ->whereNotNull('codigo_unico')
            ->orderByDesc('Fecha')
            ->get([
                'codigo_unico',
                'serie',
                'numero_comprobante',
                'Fecha',
                'Costo_Total',
                'enlace_pdf'
            ]);

        return response()->json([
            'success' => true,
            'data' => $boletas
        ]);
    }

    /**
     * Mostrar detalle de una boleta (por codigo_unico)
     */
    public function showBoleta($codigoUnico)
    {
        $boleta = Sell::with(['details.product'])
            ->where('codigo_unico', $codigoUnico)
            ->where('Comprobante', 'Boleta')
            ->first();

        if (!$boleta) {
            return response()->json([
                'success' => false,
                'message' => 'Boleta no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $boleta
        ]);
    }

    /**
     * Ver PDF de la boleta
     */
    public function verPdf($codigoUnico)
    {
        $boleta = Sell::where('codigo_unico', $codigoUnico)
            ->where('Comprobante', 'Boleta')
            ->whereNotNull('enlace_pdf')
            ->first();

        if (!$boleta) {
            abort(404, 'PDF no disponible');
        }

        return redirect()->away($boleta->enlace_pdf);
    }

    /**
    * Descargar PDF de la boleta
    */
    public function descargarPdf($codigo)
    {
        $boleta = Sell::where('codigo_unico', $codigo)->firstOrFail();

        return response()->streamDownload(function () use ($boleta) {
            echo file_get_contents($boleta->enlace_pdf);
        }, 'boleta.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\DetailSell;
use App\Models\Product;
use App\Models\Category;
use App\Models\Lote;
use App\Models\Sell;

class EstadisticasController extends Controller
{
    /**
     * Pie chart: categorías más vendidas (por cantidad)
     * Devuelve labels, data (cantidad) y porcentajes
     */
    public function categoriasMasVendidas()
    {
        $rows = DB::table('detalle_venta')
            ->join('productos', 'detalle_venta.Id_Producto', '=', 'productos.id')
            ->join('categoria', 'productos.id_categoria', '=', 'categoria.Id')
            ->select('categoria.Id as category_id', 'categoria.Nombre as category_name', DB::raw('SUM(detalle_venta.Cantidad) as total'))
            ->groupBy('categoria.Id', 'categoria.Nombre')
            ->orderByDesc('total')
            ->get();

        $totalAll = $rows->sum('total');

        $labels = $rows->pluck('category_name');
        $data = $rows->pluck('total');
        $percentages = $rows->map(function ($r) use ($totalAll) {
            return $totalAll ? round(($r->total / $totalAll) * 100, 2) : 0;
        });

        return response()->json([
            'labels' => $labels,
            'data' => $data,
            'percentages' => $percentages,
        ]);
    }

    /**
     * Bar chart: lotes activos por producto filtrado por categoría (opcional)
     * Query param: category_id (opcional)
     * Devuelve labels (productos) y data (unidades disponibles sumadas de lotes activos)
     */
    public function lotesActivosPorCategoria(Request $request)
    {
        $categoryId = $request->query('category_id');

        $rows = DB::table('lote')
            ->join('productos', 'lote.Id_Producto', '=', 'productos.id')
            ->when($categoryId, function ($q) use ($categoryId) {
                return $q->where('productos.id_categoria', $categoryId);
            })
            ->where('lote.Estado', 'Activo')
            ->select('productos.id as product_id', 'productos.nombre as product_name', DB::raw('SUM(lote.Cantidad) as total_cantidad'))
            ->groupBy('productos.id', 'productos.nombre')
            ->orderByDesc('total_cantidad')
            ->get();

        $labels = $rows->pluck('product_name');
        $data = $rows->pluck('total_cantidad');

        return response()->json([
            'labels' => $labels,
            'data' => $data,
        ]);
    }

    /**
     * Line chart: ventas por mes para un año dado
     * Query param: year (opcional, por defecto año actual)
     * Devuelve labels (meses) y data (total de ventas por mes)
     */
    public function ventasPorMes(Request $request)
    {
        $year = $request->query('year', date('Y'));

        $rows = DB::table('ventas')
            ->select(DB::raw('MONTH(Fecha) as month'), DB::raw('SUM(Costo_Total) as total'))
            ->whereYear('Fecha', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $months = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        $data = [];
        for ($m = 1; $m <= 12; $m++) {
            $data[] = isset($rows[$m]) ? (float) $rows[$m]->total : 0.0;
        }

        return response()->json([
            'labels' => $months,
            'data' => $data,
            'year' => (int) $year,
        ]);
    }
}

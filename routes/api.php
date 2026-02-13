<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LoteController;
use App\Http\Controllers\SellController;
use App\Http\Controllers\DirectionController;
use App\Http\Controllers\EstadisticasController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ChatDataController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\ImagenController;
use App\Http\Controllers\ComprobanteController;
use App\Http\Controllers\MovilController;
use App\Http\Controllers\MovilSellController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Aquí puedes registrar las rutas de la API para tu aplicación. Estas
| rutas son cargadas por el RouteServiceProvider dentro de un grupo al que
| se le asigna el grupo de middleware "api". ¡Disfruta creando tu API!
|
*/
Route::get('/login', function () {
    return response()->json([
        'message' => 'Este endpoint solo acepta POST'
    ], 405);
});

//Clientes se registran por la app movil, no hay registro público para ellos
Route::post('/login', [MovilController::class, 'login']);
Route::post('/register', [MovilController::class, 'register']);

// Endpoint para obtener productos con filtros para la app móvil, no necesita token porque es información pública
Route::get('/movil/productos', [MovilController::class, 'index']);

// Administradores, no hay registro público para ellos
Route::post('/admin/login', [AuthController::class, 'adminLogin']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/estadisticas/categorias-mas-vendidas', [EstadisticasController::class, 'categoriasMasVendidas']);
    Route::get('/estadisticas/lotes-activos-por-categoria', [EstadisticasController::class, 'lotesActivosPorCategoria']);
    Route::get('/estadisticas/ventas-por-mes-y-tipo-entrega', [EstadisticasController::class, 'ventasPorMesYTipoEntrega']);
    Route::get('/estadisticas/contar-clientes', [EstadisticasController::class, 'contarClientes']);
    Route::get('/estadisticas/producto-mas-vendido', [EstadisticasController::class, 'productoMasVendido']);
    Route::get('/estadisticas/ganancias-anio/{year?}', [EstadisticasController::class, 'gananciasAnio']);
    Route::get('/estadisticas/total-ventas-mes/{month}/{year?}', [EstadisticasController::class, 'totalVentasMes']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/admin/logout', [AuthController::class, 'adminLogout']);

    Route::get('/usuarios', [UsuarioController::class, 'index']);
    Route::get('/usuarios/{id}', [UsuarioController::class, 'show']);
    Route::post('/usuarios', [UsuarioController::class, 'store']);
    Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy']);
    Route::patch('/usuarios/{id}', [UsuarioController::class, 'update']);
    Route::get('/usuarios/{id}/can-delete', [UsuarioController::class, 'canDelete']);

    Route::get('/productos', [ProductController::class, 'index']);
    Route::post('/productos', [ProductController::class, 'store']);
    Route::put('/productos/{id}', [ProductController::class, 'update']);    // <-- Asegúrate de tener esta línea
    Route::delete('/productos/{id}', [ProductController::class, 'destroy']);
    Route::get('/productos/{id}/can-delete', [ProductController::class, 'canDelete']);
    
    Route::post('/productos/{producto}/imagenes', [ImagenController::class, 'store']);
    Route::get('/productos/{producto}/imagenes', [ImagenController::class, 'show']);
    Route::delete('/imagenes-secundarias/{id}', [ImagenSecundariaController::class, 'destroy']);

    Route::get('/categorias', [CategoryController::class, 'index']);
    Route::post('/categorias', [CategoryController::class, 'store']);
    Route::put('/categorias/{id}', [CategoryController::class, 'update']);
    Route::delete('/categorias/{id}', [CategoryController::class, 'destroy']);

    Route::get('/lotes', [LoteController::class, 'index']);
    Route::post('/lotes', [LoteController::class, 'store']);
    Route::put('/lotes/{id}', [LoteController::class, 'update']);
    Route::delete('/lotes/{id}', [LoteController::class, 'destroy']);
    Route::get('/lotes/{id}/can-delete', [LoteController::class, 'canDelete']);

    Route::get('/ventas', [SellController::class, 'index']);
    Route::get('/ventas/{id}', [SellController::class, 'show']);
    Route::post('/ventas', [SellController::class, 'store']);
    Route::put('/ventas/{id}', [SellController::class, 'update']);
    Route::post('/ventas/{id}/cancelar', [SellController::class, 'cancelSell']);
    //LISTA DE VENTAS EN REVISION, APROBAR VENTA, ELIMINAR VENTA
    Route::post('/ventas/{id}/cancelar-revision', [SellController::class, 'cancelarVentaEnRevision']);
    Route::post('/ventas/{id}/aprobar', [SellController::class, 'aprobarVenta']);
    Route::get('/ventas/revision', [SellController::class, 'ventasEnRevision']);

    Route::post('/directions', [DirectionController::class, 'store']);

    Route::post('/chat', [ChatController::class, 'chat']);
    Route::get('/chat/productos', [ChatDataController::class, 'productos']);
    Route::post('/chat/productos-stock', [ChatDataController::class, 'stockPorProductos']);

    Route::prefix('alerts')->group(function () {
        Route::get('/', [AlertController::class, 'index']);
        Route::get('/unread-count', [AlertController::class, 'unreadCount']);
        Route::patch('/{id}/read', [AlertController::class, 'markAsRead']);
        Route::patch('/read-all', [AlertController::class, 'markAllAsRead']);
        Route::get('/total-count', [AlertController::class, 'totalCount']);
        Route::get('/unread-index', [AlertController::class, 'unreadIndex']);
    });

    Route::delete('/alerts/{id}', [AlertController::class, 'destroy']);

    Route::prefix('comprobantes')->group(function () {
        Route::get('/boletas', [ComprobanteController::class, 'boletas']);
        Route::get('/boletas/{codigo_unico}', [ComprobanteController::class, 'showBoleta']);
        Route::get('/boletas/{codigo_unico}/pdf', [ComprobanteController::class, 'verPdf']);
        Route::get('/descargar/{codigo_unico}/pdf', [ComprobanteController::class, 'descargarPdf']);
    });

    // endpoints para movil
    Route::post('/movil/ventas', [MovilSellController::class, 'store']);
    Route::get('/movil/ventas/{id}', [MovilSellController::class, 'show']);
    Route::post('/movil/ventas/validar-entrega', [MovilSellController::class, 'validarEntregaPorQR']);
});

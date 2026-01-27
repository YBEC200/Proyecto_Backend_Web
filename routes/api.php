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
Route::post('/login', [AuthController::class, 'login']);
Route::post('/admin/login', [AuthController::class, 'adminLogin']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/estadisticas/categorias-mas-vendidas', [EstadisticasController::class, 'categoriasMasVendidas']);
    Route::get('/estadisticas/lotes-activos-por-categoria', [EstadisticasController::class, 'lotesActivosPorCategoria']);
    Route::get('/estadisticas/ventas-mensuales', [EstadisticasController::class, 'ventasMensuales']);
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/admin/logout', [AuthController::class, 'adminLogout']);

    Route::get('/usuarios', [UsuarioController::class, 'index']);
    Route::post('/usuarios', [UsuarioController::class, 'store']);
    Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy']);
    Route::put('/usuarios/{id}', [UsuarioController::class, 'update']);
    Route::get('/usuarios/{id}/can-delete', [UsuarioController::class, 'canDelete']);

    Route::get('/productos', [ProductController::class, 'index']);
    Route::post('/productos', [ProductController::class, 'store']);
    Route::put('/productos/{id}', [ProductController::class, 'update']);    // <-- Asegúrate de tener esta línea
    Route::delete('/productos/{id}', [ProductController::class, 'destroy']);
    Route::get('/productos/{id}/can-delete', [ProductController::class, 'canDelete']);


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
    Route::post('/ventas/validar-entrega', [SellController::class, 'validarEntregaPorQR']);

    Route::post('/directions', [DirectionController::class, 'store']);
});

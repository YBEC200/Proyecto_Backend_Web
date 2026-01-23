<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Sell;
use App\Models\Product;
use App\Models\DetailLote;

class DetailSell extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'Id';
    protected $table = 'detalle_venta';

    protected $fillable = [
        'Id_Venta',
        'Id_Producto',
        'Cantidad',
        'Costo'
    ];

    protected $casts = [
        'Cantidad' => 'integer',
        'Costo' => 'decimal:2',
    ];

    /**
     * Relación: Un detalle de venta pertenece a una venta
     */
    public function sell()
    {
        return $this->belongsTo(Sell::class, 'Id_Venta', 'Id');
    }

    /**
     * Relación: Un detalle de venta pertenece a un producto
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'Id_Producto', 'Id');
    }

    /**
     * Relación: Un detalle de venta tiene muchos detalles de lote
     */
    public function detailLotes()
    {
        return $this->hasMany(DetailLote::class, 'Id_Detalle_Venta', 'Id');
    }
}

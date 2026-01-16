<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailSell extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'detalle_venta';

    protected $fillable = [
        'id_venta',
        'id_producto',
        'cantidad',
        'costo'
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'costo' => 'decimal:2',
    ];

    /**
     * Relación: Un detalle de venta pertenece a una venta
     */
    public function sell()
    {
        return $this->belongsTo(Sell::class, 'id_venta', 'id');
    }

    /**
     * Relación: Un detalle de venta pertenece a un producto
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'id_producto');
    }

    /**
     * Relación: Un detalle de venta tiene muchos detalles de lote
     */
    public function detailLotes()
    {
        return $this->hasMany(DetailLote::class, 'id_detalle_venta', 'id');
    }
}

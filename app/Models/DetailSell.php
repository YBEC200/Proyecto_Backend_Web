<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Sell;
use App\Models\DetailLote;

class DetalleVenta extends Model
{
    use HasFactory;

    protected $table = 'detalle_venta';
    protected $primaryKey = 'Id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'Id_Venta',
        'Id_Producto',
        'Cantidad',
        'Costo',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'Id_Venta', 'Id');
    }

    public function loteDetalles()
    {
        return $this->hasMany(DetailLote::class, 'Id_Detalle_Venta', 'Id');
    }
}
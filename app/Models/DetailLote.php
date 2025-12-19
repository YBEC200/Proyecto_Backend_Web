<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailLote extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'detalle_lote';

    protected $fillable = [
        'id_detalle_venta',
        'id_lote',
        'cantidad'
    ];

    protected $casts = [
        'cantidad' => 'integer',
    ];

    /**
     * Relación: Un detalle de lote pertenece a un detalle de venta
     */
    public function detailSell()
    {
        return $this->belongsTo(DetailSell::class, 'id_detalle_venta');
    }

    /**
     * Relación: Un detalle de lote pertenece a un lote
     */
    public function lote()
    {
        return $this->belongsTo(Lote::class, 'id_lote', 'Id');
    }
}

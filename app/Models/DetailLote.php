<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\DetailSell;
use App\Models\Lote;

class DetailLote extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'detalle_lote';
    protected $primaryKey = 'Id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'Id_Detalle_Venta',
        'Id_Lote',
        'Cantidad'
    ];

    protected $casts = [
        'Cantidad' => 'integer',
    ];

    /**
     * Relación: Un detalle de lote pertenece a un detalle de venta
     */
    public function detailSell()
    {
        return $this->belongsTo(DetailSell::class, 'Id_Detalle_Venta', 'Id');
    }

    /**
     * Relación: Un detalle de lote pertenece a un lote
     */
    public function lote()
    {
        return $this->belongsTo(Lote::class, 'Id_Lote', 'Id');
    }
}

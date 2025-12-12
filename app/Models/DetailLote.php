<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleLote extends Model
{
    use HasFactory;

    protected $table = 'detalle_lote';
    protected $primaryKey = 'Id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'Id_Detalle_Venta',
        'Id_Lote',
        'Cantidad',
    ];

    public function lote()
    {
        return $this->belongsTo(\App\Models\Lote::class, 'Id_Lote', 'Id');
    }
}
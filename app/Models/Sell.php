<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\DetailSell;

class Sell extends Model
{
    use HasFactory;

    protected $table = 'ventas';
    protected $primaryKey = 'Id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'Id_Usuario',
        'Id_Metodo_Pago',
        'Id_Comprobante',
        'Id_Direccion',
        'Fecha',
        'Costo_total',
        'estado',
    ];

    public function detalles()
    {
        return $this->hasMany(DetailSell::class, 'Id_Venta', 'Id');
    }
}

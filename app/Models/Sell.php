<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\DetailSell;
use App\Models\DetailLote;
use App\Models\User;
use App\Models\Direction;

class Sell extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'ventas';

    protected $fillable = [
        'Id_Usuario',
        'Metodo_Pago',
        'Comprobante',
        'Id_Direccion',
        'Fecha',
        'Costo_Total',
        'Estado'
    ];

    protected $casts = [
        'Fecha' => 'datetime',
        'Costo_Total' => 'decimal:2',
    ];

    /**
     * Relación: Una venta pertenece a un usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'Id_Usuario', 'Id');
    }

    /**
     * Relación: Una venta pertenece a una dirección
     */
    public function direction()
    {
        return $this->belongsTo(Direction::class, 'Id_Direccion', 'Id');
    }

    /**
     * Relación: Una venta tiene muchos detalles
     */
    public function details()
    {
        return $this->hasMany(DetailSell::class, 'Id_Venta', 'Id');
    }

    /**
     * Relación: Obtener todos los detalles de lote de una venta a través de detalles de venta
     */
    public function detailLotes()
    {
        return $this->hasManyThrough(
            DetailLote::class,
            DetailSell::class,
            'Id_Venta',
            'Id_Detalle_Venta',
            'Id',
            'Id'
        );
    }
}

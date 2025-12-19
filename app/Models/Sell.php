<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sell extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'sells';

    protected $fillable = [
        'id_usuario',
        'metodo_pago',
        'comprobante',
        'id_direccion',
        'fecha',
        'costo_total',
        'estado'
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'costo_total' => 'decimal:2',
    ];

    /**
     * Relación: Una venta pertenece a un usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    /**
     * Relación: Una venta pertenece a una dirección
     */
    public function direction()
    {
        return $this->belongsTo(Direction::class, 'id_direccion');
    }

    /**
     * Relación: Una venta tiene muchos detalles
     */
    public function details()
    {
        return $this->hasMany(DetailSell::class, 'id_venta');
    }

    /**
     * Relación: Obtener todos los detalles de lote de una venta a través de detalles de venta
     */
    public function detailLotes()
    {
        return $this->hasManyThrough(
            DetailLote::class,
            DetailSell::class,
            'id_venta',
            'id_detalle_venta',
            'id',
            'id'
        );
    }
}

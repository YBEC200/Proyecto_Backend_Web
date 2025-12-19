<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Direction extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'directions';

    protected $fillable = [
        'ciudad',
        'calle',
        'referencia'
    ];

    /**
     * Relación: Una dirección pertenece a muchas ventas
     */
    public function sells()
    {
        return $this->hasMany(Sell::class, 'id_direccion');
    }
}

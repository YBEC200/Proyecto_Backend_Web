<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class Lote extends Model
{
    use HasFactory;
    // La tabla no tiene created_at/updated_at
    public $timestamps = false;

    protected $table = 'lote';
    
    protected $primaryKey = 'Id';
    public $incrementing = true; // o false si no es autoincrement
    protected $keyType = 'int';

    protected $fillable = [
        'Lote',
        'Id_Producto',
        'Fecha_Registro',
        'Cantidad',
        'Estado',
    ];

    // Cast para el enum
    protected $casts = [
        'Fecha_Registro' => 'date',
        'Cantidad' => 'integer',
        'Estado' => 'string', // Cambia a 'string' si es enum
    ];

    // Relación con producto
    public function producto()
    {
        return $this->belongsTo(Product::class, 'Id_Producto', 'id');
    }

    /**
     * Relación: Un lote tiene muchos detalles de lote
     */
    public function detailLotes()
    {
        return $this->hasMany(DetailLote::class, 'id_lote', 'Id');
    }
}

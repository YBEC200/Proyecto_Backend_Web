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

    protected $fillable = [
        'Lote',
        'Id_Producto',
        'Fecha_Registro',
        'Cantidad',
        'Estado',
    ];

    // Casts para tipos correctos
    protected $casts = [
        'Fecha_Registro' => 'date',
        'Cantidad' => 'integer',
    ];

    // Relación con producto
    public function producto()
    {
        return $this->belongsTo(Product::class, 'Id_Producto', 'id');
    }
}

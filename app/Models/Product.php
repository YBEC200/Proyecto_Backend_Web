<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Categoria;

class Product extends Model
{
    use HasFactory;

    // Desactivar timestamps si la tabla no tiene created_at/updated_at
    public $timestamps = false;

    protected $table = 'productos';
    protected $fillable = [
        'nombre',
        'descripcion',
        'marca',
        'id_categoria',
        'estado',
        'costo_unit',
        'imagen_path',
        'fecha_registro',
        'lotes'
    ];

    protected $casts = [
        'costo_unit' => 'float',
        'lotes' => 'integer',
        'fecha_registro' => 'datetime',
    ];

    // Relación con categoría
    public function categoria()
    {
        return $this->belongsTo(Category::class, 'id_categoria');
    }

    // Relación con lotes (tabla 'lote', PK 'Id', FK 'Id_Producto')
    public function lotes()
    {
        return $this->hasMany(Lote::class, 'Id_Producto', 'id');
    }
}

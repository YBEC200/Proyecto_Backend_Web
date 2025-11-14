<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Categoria;

class Product extends Model
{
    use HasFactory;

    protected $table = 'productos'; // Nombre de la tabla
    protected $fillable = [
        'nombre',
        'descripcion',
        'marca',
        'id_categoria',
        'estado',
        'costo_unit',
        'imagen_path',
        'fecha_registro'
    ];

    // Relación con categoría
    public function categoria()
    {
        return $this->belongsTo(Category::class, 'id_categoria');
    }
}

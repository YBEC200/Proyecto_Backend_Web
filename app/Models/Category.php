<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class Category extends Model
{
    use HasFactory;
    // La tabla no tiene created_at/updated_at
    protected $primaryKey = 'Id';
    public $incrementing = true; // o false si no es autoincrement
    protected $keyType = 'int'; 
    public $timestamps = false;
    protected $table = 'categoria'; // Cambia si tu tabla se llama diferente

    protected $fillable = [
        'Nombre',
        'Descripcion'
    ];

    public function productos()
    {
        return $this->hasMany(Product::class, 'id_categoria', 'id');
    }
}

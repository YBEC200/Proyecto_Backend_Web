<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class Category extends Model
{
    use HasFactory;
    // La tabla no tiene created_at/updated_at
    public $timestamps = false;
    protected $table = 'categoria'; // Cambia si tu tabla se llama diferente

    protected $fillable = [
        'nombre',
        'descripcion'
    ];

}

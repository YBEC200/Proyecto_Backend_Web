<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categoria'; // Cambia si tu tabla se llama diferente

    protected $fillable = [
        'nombre',
        'descripcion'
    ];

}

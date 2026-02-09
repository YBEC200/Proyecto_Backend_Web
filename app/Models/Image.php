<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $table = 'producto_imagenes';

    protected $fillable = [
        'producto_id',
        'ruta',
    ];

    public function producto()
    {
        return $this->belongsTo(Product::class, 'producto_id', 'id');
    }
}

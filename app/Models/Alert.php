<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    protected $table = 'alerts';

    protected $fillable = [
        'tipo',
        'severidad',
        'titulo',
        'mensaje',
        'leida',
        'user_id',
        'venta_id',
        'lote_id',
        'producto_id',
        'metadata',
    ];

    protected $casts = [
        'leida' => 'boolean',
        'metadata' => 'array',
    ];

    /* =============================
     * Relaciones
     * ============================= */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function venta()
    {
        return $this->belongsTo(Sell::class, 'venta_id', 'Id');
    }

    public function producto()
    {
        return $this->belongsTo(Product::class, 'producto_id');
    }

    public function lote()
    {
        return $this->belongsTo(Lote::class, 'lote_id', 'Id');
    }
}
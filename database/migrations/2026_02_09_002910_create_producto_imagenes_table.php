<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_imagenes', function (Blueprint $table) {
            $table->id();

            // Relación con productos
            $table->foreignId('producto_id')
                ->references('id')
                ->on('productos')
                ->onDelete('restrict')
                ->onUpdate('cascade')
                ->constrained('productos')
                ->cascadeOnDelete();

            // Ruta de la imagen
            $table->string('ruta', 255);

            $table->timestamps();

            // Índices útiles
            $table->index('producto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_imagenes');
    }
};

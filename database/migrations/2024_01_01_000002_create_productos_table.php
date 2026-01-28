<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->string('marca', 100)->nullable();
            $table->unsignedBigInteger('id_categoria');
            $table->enum('estado', ['Agotado', 'Abastecido', 'Inactivo'])->default('Abastecido');
            $table->decimal('costo_unit', 10, 2);
            $table->string('imagen_path', 255)->nullable();
            $table->dateTime('fecha_registro')->nullable();
            
            // Llave foránea
            $table->foreign('id_categoria')
                ->references('Id')
                ->on('categoria')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};

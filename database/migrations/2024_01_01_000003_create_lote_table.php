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
        Schema::create('lote', function (Blueprint $table) {
            $table->id('Id');
            $table->string('Lote', 80);
            $table->unsignedBigInteger('Id_Producto');
            // Agregar el precio de compra del lote
            $table->decimal('Precio_Compra', 10, 2)->nullable();
            $table->date('Fecha_Registro')->nullable();
            $table->integer('Cantidad')->default(0);
            $table->enum('Estado', ['Activo', 'Inactivo'])->default('Activo');
            
            // Llave foránea
            $table->foreign('Id_Producto')
                ->references('id')
                ->on('productos')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lote');
    }
};

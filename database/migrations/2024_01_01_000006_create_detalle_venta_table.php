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
        Schema::create('detalle_venta', function (Blueprint $table) {
            $table->id('Id');
            $table->unsignedBigInteger('Id_Venta');
            $table->unsignedBigInteger('Id_Producto');
            $table->integer('Cantidad')->default(1);
            $table->decimal('Costo', 10, 2)->default(0);
            
            // Llaves foráneas
            $table->foreign('Id_Venta')
                ->references('Id')
                ->on('ventas')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            
            $table->foreign('Id_Producto')
                ->references('id')
                ->on('productos')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_venta');
    }
};

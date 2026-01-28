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
        Schema::create('detalle_lote', function (Blueprint $table) {
            $table->id('Id');
            $table->unsignedBigInteger('Id_Detalle_Venta');
            $table->unsignedBigInteger('Id_Lote');
            $table->integer('Cantidad')->default(1);
            
            // Llaves foráneas
            $table->foreign('Id_Detalle_Venta')
                ->references('Id')
                ->on('detalle_venta')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            
            $table->foreign('Id_Lote')
                ->references('Id')
                ->on('lote')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_lote');
    }
};

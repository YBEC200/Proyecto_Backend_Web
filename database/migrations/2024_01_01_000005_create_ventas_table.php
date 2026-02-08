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
        Schema::create('ventas', function (Blueprint $table) {
            $table->id('Id');
            $table->unsignedBigInteger('Id_Usuario');
            $table->enum('Metodo_Pago', ['Efectivo', 'Tarjeta', 'Deposito', 'Yape'])->default('Efectivo');
            $table->enum('Comprobante', ['Boleta', 'Factura'])->default('Boleta');
            $table->unsignedBigInteger('Id_Direccion')->nullable(); // Nullable porque no todas las ventas necesitan dirección
            $table->dateTime('Fecha');
            $table->decimal('Costo_Total', 10, 2)->default(0);
            $table->enum('estado', ['Cancelado', 'Entregado', 'Pendiente', 'En Revision'])->default('Pendiente');
            $table->enum('tipo_entrega', ['Envío a Domicilio', 'Recojo en Tienda'])->default('Recojo en Tienda');
            $table->string('qr_token', 255)->nullable();
            
            // Llaves foráneas
            $table->foreign('Id_Usuario')
                ->references('id')
                ->on('users')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            
            $table->foreign('Id_Direccion')
                ->references('Id')
                ->on('direccion')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};

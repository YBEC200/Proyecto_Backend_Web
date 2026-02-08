<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();

            /* =============================
             * Tipo y severidad de la alerta
             * ============================= */
            $table->enum('tipo', ['PRODUCTO', 'VENTA'])
                  ->default('PRODUCTO');

            $table->enum('severidad', ['baja', 'media', 'alta', 'critica'])
                  ->default('media');

            /* =============================
             * Contenido
             * ============================= */
            $table->string('titulo', 150);
            $table->text('mensaje');

            /* =============================
             * Estado de la alerta
             * ============================= */
            $table->boolean('leida')->default(false);

            /* =============================
             * Relaciones
             * ============================= */

            // Usuario (solo cuando aplique, ej: ventas)
            $table->unsignedBigInteger('user_id')->nullable();

            // Venta relacionada (alertas por estado, revisión, etc.)
            $table->unsignedBigInteger('venta_id')->nullable();

            // Producto relacionado (stock bajo, lotes críticos, etc.)
            $table->unsignedBigInteger('producto_id')->nullable();

            // Lote relacionado (opcional, para alertas específicas de lotes)
            $table->unsignedBigInteger('lote_id')->nullable();

            /* =============================
             * Datos adicionales
             * ============================= */
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete()
                ->onUpdate('cascade');

            $table->foreign('venta_id')
                ->references('Id')
                ->on('ventas')
                ->cascadeOnDelete()
                ->onUpdate('cascade');

            $table->foreign('producto_id')
                ->references('id')
                ->on('productos')
                ->cascadeOnDelete()
                ->onUpdate('cascade');

            $table->foreign('lote_id')
                ->references('Id')
                ->on('lotes')
                ->cascadeOnDelete()
                ->onUpdate('cascade');

            /* =============================
             * Índices útiles
             * ============================= */
            $table->index(['tipo', 'severidad']);
            $table->index('leida');
            $table->index('venta_id');
            $table->index('producto_id');
            $table->index('lote_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
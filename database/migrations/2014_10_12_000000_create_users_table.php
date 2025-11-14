<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // Equivale a Id INT AUTO_INCREMENT PRIMARY KEY
            $table->string('nombre', 150);
            $table->string('correo', 150)->unique();
            $table->string('password_hash', 255);
            $table->enum('rol', ['Administrador', 'Empleado', 'Cliente'])->default('Empleado');
            $table->timestamp('fecha_registro')->useCurrent();
            $table->enum('estado', ['Activo', 'Inactivo'])->default('Activo');
            $table->timestamps(); // Opcional: para created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};

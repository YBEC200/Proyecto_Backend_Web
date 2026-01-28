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
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('correo', 150)->unique();
            $table->string('password_hash');
            $table->enum('rol', ['Administrador', 'Empleado', 'Cliente'])->default('Cliente');
            $table->enum('estado', ['Activo', 'Inactivo'])->default('Activo');
            $table->dateTime('fecha_registro')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

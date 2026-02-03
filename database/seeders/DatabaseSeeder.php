<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use App\Models\Lote;
use App\Models\User;
use App\Models\Direction;
use App\Models\Sell;
use App\Models\DetailSell;
use App\Models\DetailLote;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear usuarios
        $admin = User::create([
            'nombre' => 'Admin Usuario',
            'correo' => 'admin@example.com',
            'password_hash' => bcrypt('123'),
            'rol' => 'Administrador',
            'estado' => 'Activo',
            'fecha_registro' => now()
        ]);
    }
}

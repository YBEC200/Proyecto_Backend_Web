<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Agregar columna para almacenar el contador de lotes si no existe
        if (!Schema::hasColumn('productos', 'lotes')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->integer('lotes')->default(0)->after('costo_unit');
            });
        }

        // ==========================================
        // TRIGGER: Actualizar contador de lotes en producto (INSERT en lote)
        // ==========================================
        DB::unprepared('
            CREATE TRIGGER IF NOT EXISTS trigger_lote_insert_count
            AFTER INSERT ON lote
            FOR EACH ROW
            BEGIN
                UPDATE productos 
                SET lotes = (SELECT COUNT(*) FROM lote WHERE Id_Producto = NEW.Id_Producto)
                WHERE id = NEW.Id_Producto;
            END
        ');

        // ==========================================
        // TRIGGER: Actualizar contador de lotes en producto (DELETE en lote)
        // ==========================================
        DB::unprepared('
            CREATE TRIGGER IF NOT EXISTS trigger_lote_delete_count
            AFTER DELETE ON lote
            FOR EACH ROW
            BEGIN
                UPDATE productos 
                SET lotes = (SELECT COUNT(*) FROM lote WHERE Id_Producto = OLD.Id_Producto)
                WHERE id = OLD.Id_Producto;
            END
        ');

        // ==========================================
        // TRIGGER: Actualizar estado de lote basado en cantidad (INSERT/UPDATE)
        // ==========================================
        DB::unprepared('
            CREATE TRIGGER IF NOT EXISTS trigger_lote_update_estado
            BEFORE INSERT ON lote
            FOR EACH ROW
            BEGIN
                IF NEW.Cantidad = 0 THEN
                    SET NEW.Estado = "Inactivo";
                ELSEIF NEW.Cantidad > 0 THEN
                    SET NEW.Estado = "Activo";
                END IF;
            END
        ');

        // ==========================================
        // TRIGGER: Actualizar estado de lote después de UPDATE (cantidad cambio)
        // ==========================================
        DB::unprepared('
            CREATE TRIGGER IF NOT EXISTS trigger_lote_update_estado_on_cantidad
            BEFORE UPDATE ON lote
            FOR EACH ROW
            BEGIN
                IF NEW.Cantidad = 0 THEN
                    SET NEW.Estado = "Inactivo";
                ELSEIF NEW.Cantidad > 0 AND OLD.Estado = "Inactivo" THEN
                    SET NEW.Estado = "Activo";
                END IF;
            END
        ');

        // ==========================================
        // TRIGGER: Actualizar estado de producto basado en sus lotes (INSERT lote)
        // ==========================================
        DB::unprepared('
            CREATE TRIGGER IF NOT EXISTS trigger_producto_estado_after_lote_insert
            AFTER INSERT ON lote
            FOR EACH ROW
            BEGIN
                DECLARE cantidad_activa INT;
                DECLARE cantidad_total INT;
                
                SELECT COALESCE(SUM(Cantidad), 0) INTO cantidad_activa
                FROM lote 
                WHERE Id_Producto = NEW.Id_Producto AND Estado = "Activo";
                
                SELECT COALESCE(SUM(Cantidad), 0) INTO cantidad_total
                FROM lote 
                WHERE Id_Producto = NEW.Id_Producto;
                
                IF cantidad_total = 0 THEN
                    UPDATE productos SET estado = "Inactivo" WHERE id = NEW.Id_Producto;
                ELSEIF cantidad_activa = 0 THEN
                    UPDATE productos SET estado = "Agotado" WHERE id = NEW.Id_Producto;
                ELSE
                    UPDATE productos SET estado = "Abastecido" WHERE id = NEW.Id_Producto;
                END IF;
            END
        ');

        // ==========================================
        // TRIGGER: Actualizar estado de producto basado en sus lotes (UPDATE lote)
        // ==========================================
        DB::unprepared('
            CREATE TRIGGER IF NOT EXISTS trigger_producto_estado_after_lote_update
            AFTER UPDATE ON lote
            FOR EACH ROW
            BEGIN
                DECLARE cantidad_activa INT;
                DECLARE cantidad_total INT;
                
                SELECT COALESCE(SUM(Cantidad), 0) INTO cantidad_activa
                FROM lote 
                WHERE Id_Producto = NEW.Id_Producto AND Estado = "Activo";
                
                SELECT COALESCE(SUM(Cantidad), 0) INTO cantidad_total
                FROM lote 
                WHERE Id_Producto = NEW.Id_Producto;
                
                IF cantidad_total = 0 THEN
                    UPDATE productos SET estado = "Inactivo" WHERE id = NEW.Id_Producto;
                ELSEIF cantidad_activa = 0 THEN
                    UPDATE productos SET estado = "Agotado" WHERE id = NEW.Id_Producto;
                ELSE
                    UPDATE productos SET estado = "Abastecido" WHERE id = NEW.Id_Producto;
                END IF;
            END
        ');

        // ==========================================
        // TRIGGER: Actualizar estado de producto basado en sus lotes (DELETE lote)
        // ==========================================
        DB::unprepared('
            CREATE TRIGGER IF NOT EXISTS trigger_producto_estado_after_lote_delete
            AFTER DELETE ON lote
            FOR EACH ROW
            BEGIN
                DECLARE cantidad_activa INT;
                DECLARE cantidad_total INT;
                
                SELECT COALESCE(SUM(Cantidad), 0) INTO cantidad_activa
                FROM lote 
                WHERE Id_Producto = OLD.Id_Producto AND Estado = "Activo";
                
                SELECT COALESCE(SUM(Cantidad), 0) INTO cantidad_total
                FROM lote 
                WHERE Id_Producto = OLD.Id_Producto;
                
                IF cantidad_total = 0 THEN
                    UPDATE productos SET estado = "Inactivo" WHERE id = OLD.Id_Producto;
                ELSEIF cantidad_activa = 0 THEN
                    UPDATE productos SET estado = "Agotado" WHERE id = OLD.Id_Producto;
                ELSE
                    UPDATE productos SET estado = "Abastecido" WHERE id = OLD.Id_Producto;
                END IF;
            END
        ');

        // ==========================================
        // TRIGGER: Actualizar cantidad de lote después de detalle_lote (venta)
        // ==========================================
        DB::unprepared('
            CREATE TRIGGER IF NOT EXISTS trigger_lote_reduce_cantidad_on_venta
            AFTER INSERT ON detalle_lote
            FOR EACH ROW
            BEGIN
                UPDATE lote 
                SET Cantidad = Cantidad - NEW.Cantidad
                WHERE Id = NEW.Id_Lote;
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar triggers
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_lote_insert_count');
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_lote_delete_count');
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_lote_update_estado');
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_lote_update_estado_on_cantidad');
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_producto_estado_after_lote_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_producto_estado_after_lote_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_producto_estado_after_lote_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_lote_reduce_cantidad_on_venta');

        // Eliminar columna si existe
        if (Schema::hasColumn('productos', 'lotes')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->dropColumn('lotes');
            });
        }
    }
};

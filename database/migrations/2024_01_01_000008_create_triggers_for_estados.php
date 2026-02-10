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
                    UPDATE productos SET estado = "Agotado" WHERE id = NEW.Id_Producto;
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

        // =====================================================
        // TRIGGER: Crear alerta al insertar venta
        // =====================================================
        DB::unprepared('
            CREATE TRIGGER IF NOT EXISTS trigger_alerta_venta_insert
            AFTER INSERT ON ventas
            FOR EACH ROW
            BEGIN
                IF NEW.estado = "Pendiente" THEN
                    INSERT INTO alerts (
                        tipo,
                        severidad,
                        titulo,
                        mensaje,
                        user_id,
                        venta_id,
                        created_at,
                        updated_at
                    ) VALUES (
                        "VENTA",
                        "baja",
                        "Venta pendiente",
                        CONCAT("La venta ID ", NEW.Id, " fue registrada como pendiente."),
                        NEW.Id_Usuario,
                        NEW.Id,
                        NEW.Fecha,
                        NEW.Fecha
                    );
                END IF;

                IF NEW.estado = "En Revision" THEN
                    INSERT INTO alerts (
                        tipo,
                        severidad,
                        titulo,
                        mensaje,
                        user_id,
                        venta_id,
                        created_at,
                        updated_at
                    ) VALUES (
                        "VENTA",
                        "media",
                        "Venta en revisión",
                        CONCAT("La venta ID ", NEW.Id, " fue registrada en revisión."),
                        NEW.Id_Usuario,
                        NEW.Id,
                        NEW.Fecha,
                        NEW.Fecha
                    );
                END IF;
            END
        ');

        // =====================================================
        // TRIGGER: Crear alerta cuando cambia el estado de venta
        // =====================================================
        DB::unprepared('
            CREATE TRIGGER IF NOT EXISTS trigger_alerta_venta_update
            AFTER UPDATE ON ventas
            FOR EACH ROW
            BEGIN
                IF OLD.estado <> NEW.estado THEN

                    IF NEW.estado = "Pendiente" THEN
                        INSERT INTO alerts (
                            tipo,
                            severidad,
                            titulo,
                            mensaje,
                            user_id,
                            venta_id,
                            created_at,
                            updated_at
                        ) VALUES (
                            "VENTA",
                            "baja",
                            "Venta pendiente",
                            CONCAT("La venta ID ", NEW.Id, " cambió a estado pendiente."),
                            NEW.Id_Usuario,
                            NEW.Id,
                            NEW.Fecha,
                            NEW.Fecha
                        );
                    END IF;

                    IF NEW.estado = "En Revision" THEN
                        INSERT INTO alerts (
                            tipo,
                            severidad,
                            titulo,
                            mensaje,
                            user_id,
                            venta_id,
                            created_at,
                            updated_at
                        ) VALUES (
                            "VENTA",
                            "media",
                            "Venta en revisión",
                            CONCAT("La venta ID ", NEW.Id, " cambió a estado en revisión."),
                            NEW.Id_Usuario,
                            NEW.Id,
                            NEW.Fecha,
                            NEW.Fecha
                        );
                    END IF;

                END IF;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trigger_alerta_venta_cancelada
            AFTER UPDATE ON ventas
            FOR EACH ROW
            BEGIN
                IF OLD.estado <> "Cancelado" AND NEW.estado = "Cancelado" THEN
                    INSERT INTO alerts (
                        tipo,
                        severidad,
                        titulo,
                        mensaje,
                        user_id,
                        venta_id,
                        created_at,
                        updated_at
                    ) VALUES (
                        "VENTA",
                        "alta",
                        "Venta cancelada",
                        CONCAT("La venta ID ", NEW.Id, " ha sido cancelada."),
                        NEW.Id_Usuario,
                        NEW.Id,
                        NEW.Fecha,
                        NEW.Fecha
                    );
                END IF;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trigger_alerta_lote_bajo_stock
            AFTER UPDATE ON lote
            FOR EACH ROW
            BEGIN
                -- Disparar SOLO cuando cruza el umbral (ej: 5 → 4)
                IF OLD.Cantidad >= 5 
                AND NEW.Cantidad < 5 
                AND NEW.Estado = "Activo" THEN

                    INSERT INTO alerts (
                        tipo,
                        severidad,
                        titulo,
                        mensaje,
                        producto_id,
                        lote_id,
                        created_at,
                        updated_at
                    ) VALUES (
                        "PRODUCTO",
                        "baja",
                        "Lote con bajo stock",
                        CONCAT(
                            "El lote ", NEW.Lote,
                            " del producto ID ", NEW.Id_Producto,
                            " tiene bajo stock (", NEW.Cantidad, " unidades)."
                        ),
                        NEW.Id_Producto,
                        NEW.Id,
                        NOW(),
                        NOW()
                    );
                END IF;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trigger_alerta_lote_sin_stock
            AFTER UPDATE ON lote
            FOR EACH ROW
            BEGIN
                -- Si el lote quedó sin stock
                IF NEW.Cantidad = 0 AND OLD.Cantidad > 0 THEN
                    INSERT INTO alerts (
                        tipo,
                        severidad,
                        titulo,
                        mensaje,
                        producto_id,
                        lote_id,
                        created_at,
                        updated_at
                    ) VALUES (
                        "PRODUCTO",
                        "media",
                        "Lote sin stock",
                        CONCAT("El lote ", NEW.Lote, " se ha quedado sin stock."),
                        NEW.Id_Producto,
                        NEW.Id,
                        NOW(),
                        NOW()
                    );
                END IF;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trigger_alerta_ultimo_lote_activo
            AFTER UPDATE ON lote
            FOR EACH ROW
            BEGIN
                DECLARE lotes_activos_antes INT;
                DECLARE lotes_activos_despues INT;

                -- Contar lotes activos ANTES
                SELECT COUNT(*) INTO lotes_activos_antes
                FROM lote
                WHERE Id_Producto = OLD.Id_Producto
                AND Estado = "Activo";

                -- Contar lotes activos DESPUÉS
                SELECT COUNT(*) INTO lotes_activos_despues
                FROM lote
                WHERE Id_Producto = NEW.Id_Producto
                AND Estado = "Activo";

                -- Detectar cruce del umbral: >1 → 1
                IF lotes_activos_antes > 1 
                AND lotes_activos_despues = 1 THEN

                    INSERT INTO alerts (
                        tipo,
                        severidad,
                        titulo,
                        mensaje,
                        producto_id,
                        created_at,
                        updated_at
                    ) VALUES (
                        "PRODUCTO",
                        "alta",
                        "Último lote activo",
                        CONCAT(
                            "El producto ID ", NEW.Id_Producto,
                            " se ha quedado con un solo lote activo."
                        ),
                        NEW.Id_Producto,
                        NOW(),
                        NOW()
                    );
                END IF;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER trigger_alerta_producto_agotado
            AFTER UPDATE ON productos
            FOR EACH ROW
            BEGIN
                IF OLD.estado <> "Agotado" AND NEW.estado = "Agotado" THEN
                    INSERT INTO alerts (
                        tipo,
                        severidad,
                        titulo,
                        mensaje,
                        producto_id,
                        created_at,
                        updated_at
                    ) VALUES (
                        "PRODUCTO",
                        "critica",
                        "Producto agotado",
                        CONCAT("El producto ", NEW.nombre, " se ha agotado completamente."),
                        NEW.id,
                        NOW(),
                        NOW()
                    );
                END IF;
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
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_alerta_venta_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_alerta_venta_update');
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_alerta_lote_bajo_stock');
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_alerta_lote_sin_stock');
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_alerta_ultimo_lote_activo');
        DB::unprepared('DROP TRIGGER IF EXISTS trigger_alerta_producto_agotado');

        // Eliminar columna si existe
        if (Schema::hasColumn('productos', 'lotes')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->dropColumn('lotes');
            });
        }
    }
};

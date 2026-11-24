<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $tablas = ['movimientos', 'auditoria', 'legalizaciones', 'legalizacion_detalles', 'ajustes'];

    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION kosmos_bloquear_cambios() RETURNS trigger
            LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'La tabla % es de solo inserción: % no está permitido', TG_TABLE_NAME, TG_OP
                    USING ERRCODE = 'insufficient_privilege';
            END;
            $$;
        SQL);

        foreach ($this->tablas as $t) {
            DB::unprepared("CREATE TRIGGER {$t}_solo_insercion BEFORE UPDATE OR DELETE ON {$t} FOR EACH ROW EXECUTE FUNCTION kosmos_bloquear_cambios()");
            DB::unprepared("CREATE TRIGGER {$t}_sin_truncate BEFORE TRUNCATE ON {$t} FOR EACH STATEMENT EXECUTE FUNCTION kosmos_bloquear_cambios()");
        }
    }

    public function down(): void
    {
        foreach ($this->tablas as $t) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$t}_solo_insercion ON {$t}");
            DB::unprepared("DROP TRIGGER IF EXISTS {$t}_sin_truncate ON {$t}");
        }

        DB::unprepared('DROP FUNCTION IF EXISTS kosmos_bloquear_cambios()');
    }
};

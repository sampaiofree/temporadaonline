<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ligas')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE ligas ALTER COLUMN descricao DROP NOT NULL');
            DB::statement('ALTER TABLE ligas ALTER COLUMN regras DROP NOT NULL');
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE ligas MODIFY descricao TEXT NULL');
            DB::statement('ALTER TABLE ligas MODIFY regras TEXT NULL');
        } elseif ($driver === 'sqlite') {
            // SQLite does not support ALTER COLUMN; ignore for now.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('ligas')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('UPDATE ligas SET descricao = COALESCE(descricao, \'\')');
            DB::statement('UPDATE ligas SET regras = COALESCE(regras, \'\')');
            DB::statement('ALTER TABLE ligas ALTER COLUMN descricao SET NOT NULL');
            DB::statement('ALTER TABLE ligas ALTER COLUMN regras SET NOT NULL');
        } elseif ($driver === 'mysql') {
            DB::statement('UPDATE ligas SET descricao = IFNULL(descricao, \'\')');
            DB::statement('UPDATE ligas SET regras = IFNULL(regras, \'\')');
            DB::statement('ALTER TABLE ligas MODIFY descricao TEXT NOT NULL');
            DB::statement('ALTER TABLE ligas MODIFY regras TEXT NOT NULL');
        } elseif ($driver === 'sqlite') {
            // SQLite does not support ALTER COLUMN; ignore for now.
        }
    }
};

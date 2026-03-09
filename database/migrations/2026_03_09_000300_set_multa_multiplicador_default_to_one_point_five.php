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

        DB::table('ligas')->update([
            'multa_multiplicador' => 1.50,
        ]);

        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'pgsql') {
            DB::statement('ALTER TABLE ligas ALTER COLUMN multa_multiplicador SET DEFAULT 1.50');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('ligas')) {
            return;
        }

        DB::table('ligas')->update([
            'multa_multiplicador' => 2.00,
        ]);

        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'pgsql') {
            DB::statement('ALTER TABLE ligas ALTER COLUMN multa_multiplicador SET DEFAULT 2.00');
        }
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('liga_periodos')) {
            return;
        }

        $driver = DB::getDriverName();

        match ($driver) {
            'mysql', 'mariadb' => $this->alterMysqlToDateTime(),
            'pgsql' => $this->alterPostgresToDateTime(),
            'sqlsrv' => $this->alterSqlServerToDateTime(),
            default => null, // SQLite and other drivers do not need explicit conversion here.
        };
    }

    public function down(): void
    {
        if (! Schema::hasTable('liga_periodos')) {
            return;
        }

        $driver = DB::getDriverName();

        match ($driver) {
            'mysql', 'mariadb' => $this->alterMysqlToDate(),
            'pgsql' => $this->alterPostgresToDate(),
            'sqlsrv' => $this->alterSqlServerToDate(),
            default => null,
        };
    }

    private function alterMysqlToDateTime(): void
    {
        DB::statement('ALTER TABLE liga_periodos MODIFY inicio DATETIME NOT NULL');
        DB::statement('ALTER TABLE liga_periodos MODIFY fim DATETIME NOT NULL');
    }

    private function alterPostgresToDateTime(): void
    {
        DB::statement('ALTER TABLE liga_periodos ALTER COLUMN inicio TYPE timestamp(0) without time zone USING inicio::timestamp');
        DB::statement('ALTER TABLE liga_periodos ALTER COLUMN fim TYPE timestamp(0) without time zone USING fim::timestamp');
    }

    private function alterSqlServerToDateTime(): void
    {
        DB::statement('ALTER TABLE liga_periodos ALTER COLUMN inicio DATETIME NOT NULL');
        DB::statement('ALTER TABLE liga_periodos ALTER COLUMN fim DATETIME NOT NULL');
    }

    private function alterMysqlToDate(): void
    {
        DB::statement('ALTER TABLE liga_periodos MODIFY inicio DATE NOT NULL');
        DB::statement('ALTER TABLE liga_periodos MODIFY fim DATE NOT NULL');
    }

    private function alterPostgresToDate(): void
    {
        DB::statement('ALTER TABLE liga_periodos ALTER COLUMN inicio TYPE date USING inicio::date');
        DB::statement('ALTER TABLE liga_periodos ALTER COLUMN fim TYPE date USING fim::date');
    }

    private function alterSqlServerToDate(): void
    {
        DB::statement('ALTER TABLE liga_periodos ALTER COLUMN inicio DATE NOT NULL');
        DB::statement('ALTER TABLE liga_periodos ALTER COLUMN fim DATE NOT NULL');
    }
};

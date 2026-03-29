<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('liga_leiloes')) {
            return;
        }

        $driver = DB::getDriverName();

        match ($driver) {
            'mysql', 'mariadb' => $this->alterMysqlToDateTime(),
            'pgsql' => $this->alterPostgresToDateTime(),
            'sqlsrv' => $this->alterSqlServerToDateTime(),
            default => $this->alterSqliteToDateTime(),
        };
    }

    public function down(): void
    {
        if (! Schema::hasTable('liga_leiloes')) {
            return;
        }

        $driver = DB::getDriverName();

        match ($driver) {
            'mysql', 'mariadb' => $this->alterMysqlToDate(),
            'pgsql' => $this->alterPostgresToDate(),
            'sqlsrv' => $this->alterSqlServerToDate(),
            default => $this->alterSqliteToDate(),
        };
    }

    private function alterMysqlToDateTime(): void
    {
        DB::statement('ALTER TABLE liga_leiloes MODIFY inicio DATETIME NOT NULL');
        DB::statement('ALTER TABLE liga_leiloes MODIFY fim DATETIME NOT NULL');
        DB::statement("UPDATE liga_leiloes SET inicio = DATE_FORMAT(inicio, '%Y-%m-%d 00:00:00'), fim = DATE_FORMAT(fim, '%Y-%m-%d 23:59:59')");
    }

    private function alterPostgresToDateTime(): void
    {
        DB::statement('ALTER TABLE liga_leiloes ALTER COLUMN inicio TYPE timestamp(0) without time zone USING inicio::timestamp(0)');
        DB::statement("ALTER TABLE liga_leiloes ALTER COLUMN fim TYPE timestamp(0) without time zone USING (fim::timestamp(0) + interval '23 hours 59 minutes 59 seconds')");
    }

    private function alterSqlServerToDateTime(): void
    {
        DB::statement('ALTER TABLE liga_leiloes ALTER COLUMN inicio DATETIME NOT NULL');
        DB::statement('ALTER TABLE liga_leiloes ALTER COLUMN fim DATETIME NOT NULL');
        DB::statement('UPDATE liga_leiloes SET inicio = DATEADD(day, DATEDIFF(day, 0, inicio), 0), fim = DATEADD(second, 86399, DATEADD(day, DATEDIFF(day, 0, fim), 0))');
    }

    private function alterSqliteToDateTime(): void
    {
        DB::statement("UPDATE liga_leiloes SET inicio = CASE WHEN length(inicio) = 10 THEN inicio || ' 00:00:00' ELSE inicio END, fim = CASE WHEN length(fim) = 10 THEN fim || ' 23:59:59' ELSE fim END");
    }

    private function alterMysqlToDate(): void
    {
        DB::statement('ALTER TABLE liga_leiloes MODIFY inicio DATE NOT NULL');
        DB::statement('ALTER TABLE liga_leiloes MODIFY fim DATE NOT NULL');
    }

    private function alterPostgresToDate(): void
    {
        DB::statement('ALTER TABLE liga_leiloes ALTER COLUMN inicio TYPE date USING inicio::date');
        DB::statement('ALTER TABLE liga_leiloes ALTER COLUMN fim TYPE date USING fim::date');
    }

    private function alterSqlServerToDate(): void
    {
        DB::statement('ALTER TABLE liga_leiloes ALTER COLUMN inicio DATE NOT NULL');
        DB::statement('ALTER TABLE liga_leiloes ALTER COLUMN fim DATE NOT NULL');
    }

    private function alterSqliteToDate(): void
    {
        DB::statement("UPDATE liga_leiloes SET inicio = substr(inicio, 1, 10), fim = substr(fim, 1, 10)");
    }
};

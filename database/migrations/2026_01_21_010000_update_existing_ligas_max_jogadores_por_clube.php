<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ligas')->update(['max_jogadores_por_clube' => 23]);
    }

    public function down(): void
    {
        DB::table('ligas')->update(['max_jogadores_por_clube' => 18]);
    }
};

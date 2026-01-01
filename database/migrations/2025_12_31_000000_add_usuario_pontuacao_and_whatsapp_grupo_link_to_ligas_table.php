<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ligas', function (Blueprint $table): void {
            $table->float('usuario_pontuacao', 3, 1)->nullable()->after('regras');
            $table->string('whatsapp_grupo_link')->nullable()->after('usuario_pontuacao');
        });
    }

    public function down(): void
    {
        Schema::table('ligas', function (Blueprint $table): void {
            $table->dropColumn(['usuario_pontuacao', 'whatsapp_grupo_link']);
        });
    }
};

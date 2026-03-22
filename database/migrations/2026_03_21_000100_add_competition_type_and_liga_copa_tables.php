<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partidas', function (Blueprint $table): void {
            $table->string('competition_type', 16)
                ->default('liga')
                ->after('visitante_id');

            $table->index(['liga_id', 'competition_type', 'estado'], 'partidas_liga_competicao_estado_idx');
        });

        Schema::create('liga_copa_grupos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('liga_id')->constrained('ligas')->cascadeOnDelete();
            $table->unsignedSmallInteger('ordem');
            $table->string('label', 32);
            $table->timestamps();

            $table->unique(['liga_id', 'ordem'], 'liga_copa_grupos_liga_ordem_unique');
        });

        Schema::create('liga_copa_grupo_clubes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('grupo_id')->constrained('liga_copa_grupos')->cascadeOnDelete();
            $table->foreignId('liga_clube_id')->constrained('liga_clubes')->cascadeOnDelete();
            $table->unsignedSmallInteger('ordem');
            $table->timestamps();

            $table->unique(['grupo_id', 'liga_clube_id'], 'liga_copa_grupo_clubes_grupo_clube_unique');
            $table->unique(['grupo_id', 'ordem'], 'liga_copa_grupo_clubes_grupo_ordem_unique');
            $table->unique('liga_clube_id', 'liga_copa_grupo_clubes_liga_clube_unique');
        });

        Schema::create('liga_copa_fases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('liga_id')->constrained('ligas')->cascadeOnDelete();
            $table->string('tipo', 32);
            $table->unsignedSmallInteger('ordem');
            $table->string('status', 32)->default('pendente');
            $table->timestamps();

            $table->unique(['liga_id', 'tipo'], 'liga_copa_fases_liga_tipo_unique');
            $table->unique(['liga_id', 'ordem'], 'liga_copa_fases_liga_ordem_unique');
        });

        Schema::create('liga_copa_partidas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partida_id')->constrained('partidas')->cascadeOnDelete();
            $table->foreignId('fase_id')->constrained('liga_copa_fases')->cascadeOnDelete();
            $table->foreignId('grupo_id')->nullable()->constrained('liga_copa_grupos')->nullOnDelete();
            $table->string('key_slot', 64)->nullable();
            $table->unsignedTinyInteger('perna')->nullable();
            $table->timestamps();

            $table->unique('partida_id', 'liga_copa_partidas_partida_unique');
            $table->unique(['fase_id', 'key_slot', 'perna'], 'liga_copa_partidas_fase_slot_perna_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liga_copa_partidas');
        Schema::dropIfExists('liga_copa_fases');
        Schema::dropIfExists('liga_copa_grupo_clubes');
        Schema::dropIfExists('liga_copa_grupos');

        Schema::table('partidas', function (Blueprint $table): void {
            $table->dropIndex('partidas_liga_competicao_estado_idx');
            $table->dropColumn('competition_type');
        });
    }
};

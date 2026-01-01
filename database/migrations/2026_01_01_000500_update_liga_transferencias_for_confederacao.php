<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('liga_transferencias')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF;');

            DB::statement('DROP INDEX IF EXISTS "liga_transferencias_liga_id_elencopadrao_id_index";');
            DB::statement('DROP INDEX IF EXISTS "liga_transferencias_liga_id_clube_destino_id_index";');
            DB::statement('DROP INDEX IF EXISTS "liga_transferencias_liga_id_clube_origem_id_index";');

            if (Schema::hasTable('liga_transferencias_old')) {
                Schema::dropIfExists('liga_transferencias');
            } else {
                Schema::rename('liga_transferencias', 'liga_transferencias_old');
            }

            Schema::create('liga_transferencias', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('liga_id')->constrained('ligas')->cascadeOnDelete();
                $table->foreignId('confederacao_id')->nullable()->constrained('confederacoes')->restrictOnDelete();
                $table->foreignId('liga_origem_id')->nullable()->constrained('ligas')->nullOnDelete();
                $table->foreignId('liga_destino_id')->nullable()->constrained('ligas')->nullOnDelete();
                $table->foreignId('elencopadrao_id')->constrained('elencopadrao')->cascadeOnDelete();
                $table->foreignId('clube_origem_id')->nullable()->constrained('liga_clubes')->nullOnDelete();
                $table->foreignId('clube_destino_id')->constrained('liga_clubes')->cascadeOnDelete();
                $table->enum('tipo', ['compra', 'venda', 'troca', 'multa', 'jogador_livre']);
                $table->bigInteger('valor')->default(0);
                $table->string('observacao')->nullable();
                $table->timestamps();

                $table->index(['liga_id', 'elencopadrao_id']);
                $table->index(['liga_id', 'clube_destino_id']);
                $table->index(['liga_id', 'clube_origem_id']);
                $table->index(['confederacao_id', 'elencopadrao_id']);
            });

            DB::statement('
                INSERT INTO liga_transferencias (
                    id,
                    liga_id,
                    confederacao_id,
                    liga_origem_id,
                    liga_destino_id,
                    elencopadrao_id,
                    clube_origem_id,
                    clube_destino_id,
                    tipo,
                    valor,
                    observacao,
                    created_at,
                    updated_at
                )
                SELECT
                    old.id,
                    old.liga_id,
                    (SELECT confederacao_id FROM ligas WHERE ligas.id = old.liga_id),
                    old.liga_id,
                    old.liga_id,
                    old.elencopadrao_id,
                    old.clube_origem_id,
                    old.clube_destino_id,
                    old.tipo,
                    old.valor,
                    old.observacao,
                    old.created_at,
                    old.updated_at
                FROM liga_transferencias_old old
            ');

            Schema::drop('liga_transferencias_old');

            DB::statement('PRAGMA foreign_keys=ON;');

            return;
        }

        Schema::table('liga_transferencias', function (Blueprint $table): void {
            if (! Schema::hasColumn('liga_transferencias', 'confederacao_id')) {
                $table->foreignId('confederacao_id')
                    ->nullable()
                    ->after('liga_id')
                    ->constrained('confederacoes')
                    ->restrictOnDelete();
            }
            if (! Schema::hasColumn('liga_transferencias', 'liga_origem_id')) {
                $table->foreignId('liga_origem_id')
                    ->nullable()
                    ->after('confederacao_id')
                    ->constrained('ligas')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('liga_transferencias', 'liga_destino_id')) {
                $table->foreignId('liga_destino_id')
                    ->nullable()
                    ->after('liga_origem_id')
                    ->constrained('ligas')
                    ->nullOnDelete();
            }
        });

        DB::statement('
            UPDATE liga_transferencias
            SET confederacao_id = (
                SELECT confederacao_id
                FROM ligas
                WHERE ligas.id = liga_transferencias.liga_id
            ),
            liga_origem_id = liga_id,
            liga_destino_id = liga_id
            WHERE confederacao_id IS NULL
        ');

        Schema::table('liga_transferencias', function (Blueprint $table): void {
            $table->index(['confederacao_id', 'elencopadrao_id']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('liga_transferencias')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF;');

            Schema::rename('liga_transferencias', 'liga_transferencias_old');

            Schema::create('liga_transferencias', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('liga_id')->constrained('ligas')->cascadeOnDelete();
                $table->foreignId('elencopadrao_id')->constrained('elencopadrao')->cascadeOnDelete();
                $table->foreignId('clube_origem_id')->nullable()->constrained('liga_clubes')->nullOnDelete();
                $table->foreignId('clube_destino_id')->constrained('liga_clubes')->cascadeOnDelete();
                $table->enum('tipo', ['compra', 'venda', 'troca', 'multa', 'jogador_livre']);
                $table->bigInteger('valor')->default(0);
                $table->string('observacao')->nullable();
                $table->timestamps();

                $table->index(['liga_id', 'elencopadrao_id']);
                $table->index(['liga_id', 'clube_destino_id']);
                $table->index(['liga_id', 'clube_origem_id']);
            });

            DB::statement('
                INSERT INTO liga_transferencias (
                    id,
                    liga_id,
                    elencopadrao_id,
                    clube_origem_id,
                    clube_destino_id,
                    tipo,
                    valor,
                    observacao,
                    created_at,
                    updated_at
                )
                SELECT
                    id,
                    liga_id,
                    elencopadrao_id,
                    clube_origem_id,
                    clube_destino_id,
                    tipo,
                    valor,
                    observacao,
                    created_at,
                    updated_at
                FROM liga_transferencias_old
            ');

            Schema::drop('liga_transferencias_old');

            DB::statement('PRAGMA foreign_keys=ON;');

            return;
        }

        Schema::table('liga_transferencias', function (Blueprint $table): void {
            $table->dropIndex(['confederacao_id', 'elencopadrao_id']);

            if (Schema::hasColumn('liga_transferencias', 'liga_destino_id')) {
                $table->dropConstrainedForeignId('liga_destino_id');
            }
            if (Schema::hasColumn('liga_transferencias', 'liga_origem_id')) {
                $table->dropConstrainedForeignId('liga_origem_id');
            }
            if (Schema::hasColumn('liga_transferencias', 'confederacao_id')) {
                $table->dropConstrainedForeignId('confederacao_id');
            }
        });
    }
};

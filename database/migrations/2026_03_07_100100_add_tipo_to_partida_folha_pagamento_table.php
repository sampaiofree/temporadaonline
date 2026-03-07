<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('partida_folha_pagamento')) {
            return;
        }

        if (! Schema::hasColumn('partida_folha_pagamento', 'tipo')) {
            Schema::table('partida_folha_pagamento', function (Blueprint $table): void {
                $table->string('tipo', 64)
                    ->default('debito_salario_legacy')
                    ->after('clube_id');
            });
        }

        DB::table('partida_folha_pagamento')
            ->whereNull('tipo')
            ->update(['tipo' => 'debito_salario_legacy']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('partida_folha_pagamento')) {
            return;
        }

        if (Schema::hasColumn('partida_folha_pagamento', 'tipo')) {
            Schema::table('partida_folha_pagamento', function (Blueprint $table): void {
                $table->dropColumn('tipo');
            });
        }
    }
};

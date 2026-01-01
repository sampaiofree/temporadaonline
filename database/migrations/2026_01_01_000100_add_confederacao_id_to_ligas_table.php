<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ligas', function (Blueprint $table): void {
            $table->foreignId('confederacao_id')
                ->nullable()
                ->after('max_times')
                ->constrained('confederacoes')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ligas', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('confederacao_id');
        });
    }
};

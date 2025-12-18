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
        Schema::create('liga_clube_elencos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('liga_clube_id')->constrained('liga_clubes')->cascadeOnDelete();
            $table->foreignId('elencopadrao_id')->constrained('elencopadrao')->cascadeOnDelete();
            $table->timestamps();

            $table->unique('elencopadrao_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liga_clube_elencos');
    }
};

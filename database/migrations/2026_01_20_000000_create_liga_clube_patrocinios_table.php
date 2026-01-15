<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liga_clube_patrocinios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('liga_id');
            $table->unsignedBigInteger('liga_clube_id');
            $table->unsignedBigInteger('patrocinio_id');
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            $table->unique(['liga_id', 'liga_clube_id', 'patrocinio_id'], 'liga_clube_patrocinio_unique');
            $table->index('liga_id');
            $table->index('liga_clube_id');
            $table->index('patrocinio_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liga_clube_patrocinios');
    }
};

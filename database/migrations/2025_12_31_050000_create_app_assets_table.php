<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_assets', function (Blueprint $table): void {
            $table->id();
            $table->string('favicon')->nullable();
            $table->string('logo_padrao')->nullable();
            $table->string('logo_dark')->nullable();
            $table->string('imagem_campo')->nullable();
            $table->string('background_app')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_assets');
    }
};

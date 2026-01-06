<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ligas', function (Blueprint $table) {
            $table->string('whatsapp_grupo_jid')->nullable()->after('whatsapp_grupo_link');
        });
    }

    public function down(): void
    {
        Schema::table('ligas', function (Blueprint $table) {
            $table->dropColumn('whatsapp_grupo_jid');
        });
    }
};

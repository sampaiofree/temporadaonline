<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_connections', function (Blueprint $table) {
            $table->id();
            $table->string('instance_name');
            $table->string('status')->nullable();
            $table->text('qr_code')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_status_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_connections');
    }
};

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
        Schema::table('users', function (Blueprint $table) {
            $table->string('password_reset_code_hash')->nullable()->after('email_verification_code_attempts');
            $table->timestamp('password_reset_code_expires_at')->nullable()->after('password_reset_code_hash');
            $table->timestamp('password_reset_code_sent_at')->nullable()->after('password_reset_code_expires_at');
            $table->timestamp('password_reset_code_verified_at')->nullable()->after('password_reset_code_sent_at');
            $table->unsignedSmallInteger('password_reset_code_attempts')->default(0)->after('password_reset_code_verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'password_reset_code_hash',
                'password_reset_code_expires_at',
                'password_reset_code_sent_at',
                'password_reset_code_verified_at',
                'password_reset_code_attempts',
            ]);
        });
    }
};

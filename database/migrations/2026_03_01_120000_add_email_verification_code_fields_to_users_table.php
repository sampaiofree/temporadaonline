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
            $table->string('email_verification_code_hash')->nullable()->after('email_verified_at');
            $table->timestamp('email_verification_code_expires_at')->nullable()->after('email_verification_code_hash');
            $table->timestamp('email_verification_code_sent_at')->nullable()->after('email_verification_code_expires_at');
            $table->unsignedSmallInteger('email_verification_code_attempts')->default(0)->after('email_verification_code_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_verification_code_hash',
                'email_verification_code_expires_at',
                'email_verification_code_sent_at',
                'email_verification_code_attempts',
            ]);
        });
    }
};

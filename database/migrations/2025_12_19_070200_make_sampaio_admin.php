<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('email', 'sampaio.free@gmail.com')
            ->update(['is_admin' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')
            ->where('email', 'sampaio.free@gmail.com')
            ->update(['is_admin' => false]);
    }
};

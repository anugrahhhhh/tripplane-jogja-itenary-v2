<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remember-me dihapus: cookie recaller lama tidak boleh lagi me-login-kan user tanpa 2FA.
     */
    public function up(): void
    {
        DB::table('users')->update(['remember_token' => null]);
    }

    public function down(): void
    {
        // Token lama tidak bisa dipulihkan dan memang tidak boleh dipakai lagi.
    }
};

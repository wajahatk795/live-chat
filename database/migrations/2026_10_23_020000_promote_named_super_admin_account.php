<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('email', 'superadmin@example.com')
            ->where('name', 'Super Admin')
            ->where('role', '3')
            ->update(['role' => 'super_admin']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('email', 'superadmin@example.com')
            ->where('name', 'Super Admin')
            ->where('role', 'super_admin')
            ->update(['role' => '3']);
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Get the first admin user
        $adminUser = DB::table('users')
            ->where('email', 'admin@obiikz.com')
            ->orWhere('role_id', DB::table('roles')->where('slug', 'admin')->value('id'))
            ->first();

        if ($adminUser) {
            DB::table('sales_documents')
                ->whereNull('user_id')
                ->update(['user_id' => $adminUser->id]);
        }
    }

    public function down(): void
    {
        // No need to revert user_id back to null
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Insert the system Admin role
        $adminRoleId = DB::table('roles')->insertGetId([
            'name'        => 'Admin',
            'slug'        => 'admin',
            'description' => 'Full system access. Can manage everything including users and roles.',
            'is_system'   => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // Add role_id FK column (nullable to avoid breaking existing rows temporarily)
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('is_active')->constrained('roles')->nullOnDelete();
        });

        // Migrate existing users: anyone with old role='admin' gets the admin role_id
        DB::table('users')->update(['role_id' => $adminRoleId]);

        // Drop the old string role column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->after('is_active');
        });

        DB::table('users')->update(['role' => 'admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });

        DB::table('roles')->where('slug', 'admin')->where('is_system', 1)->delete();
    }
};

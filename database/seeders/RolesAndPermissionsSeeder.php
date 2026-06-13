<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed all permissions from the canonical MODULES definition
        foreach (Permission::MODULES as $module => $config) {
            foreach ($config['actions'] as $action => $label) {
                Permission::firstOrCreate(
                    ['module' => $module, 'action' => $action],
                    ['label'  => $label]
                );
            }
        }

        $allPermIds = Permission::all()->pluck('id');

        // 2. Admin role (created in migration, sync all permissions)
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'description' => 'Full system access.', 'is_system' => true]
        );
        $adminRole->permissions()->sync($allPermIds);

        // 3. Manager — customers + sales (full CRUD) + import + dashboard
        $managerRole = Role::firstOrCreate(
            ['slug' => 'manager'],
            ['name' => 'Manager', 'description' => 'Manage customers and sales documents.', 'is_system' => false]
        );
        $managerPermIds = Permission::where(function ($q) {
            $q->whereIn('module', ['dashboard', 'customers', 'sales', 'import']);
        })->pluck('id');
        $managerRole->permissions()->sync($managerPermIds);

        // 4. Staff — dashboard read, customers read, sales create + read
        $staffRole = Role::firstOrCreate(
            ['slug' => 'staff'],
            ['name' => 'Staff', 'description' => 'View customers and create sales documents.', 'is_system' => false]
        );
        $staffPermIds = Permission::where(function ($q) {
            $q->where('module', 'dashboard')->where('action', 'read');
        })->orWhere(function ($q) {
            $q->where('module', 'customers')->where('action', 'read');
        })->orWhere(function ($q) {
            $q->where('module', 'sales')->whereIn('action', ['create', 'read']);
        })->pluck('id');
        $staffRole->permissions()->sync($staffPermIds);

        // 5. Ensure no user is left without a role (assign admin role as fallback)
        User::whereNull('role_id')->update(['role_id' => $adminRole->id]);
    }
}

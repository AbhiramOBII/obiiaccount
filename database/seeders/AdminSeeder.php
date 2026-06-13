<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('slug', 'admin')->first();

        User::firstOrCreate(
            ['email' => 'admin@obiikz.com'],
            [
                'name'               => 'Abhiram',
                'password'           => Hash::make('password'),
                'email_verified_at'  => now(),
                'role_id'            => $adminRole?->id,
                'is_active'          => true,
            ]
        );
    }
}

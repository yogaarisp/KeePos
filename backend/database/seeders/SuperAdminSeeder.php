<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'yoga99se@gmail.com'],
            [
                'full_name' => 'yoga',
                'username' => 'yoga',
                'password' => Hash::make('password123'),
                'role' => 'superadmin',
                'tenant_id' => null, // Superadmin doesn't belong to a tenant
            ]
        );
        
        echo "Superadmin yoga created successfully.\n";
    }
}

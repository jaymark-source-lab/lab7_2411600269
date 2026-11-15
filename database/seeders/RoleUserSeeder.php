<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleUserSeeder extends Seeder
{
    // one demo account per role (password: password123)
    public function run(): void
    {
        $users = [
            ['Manager User', 'manager@example.com', 'manager'],
            ['Warehouse User', 'warehouse@example.com', 'warehouse'],
            ['Staff User', 'staff@example.com', 'staff'],
        ];

        foreach ($users as [$name, $email, $role]) {
            User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => Hash::make('password123'), 'role' => $role]
            );
        }
    }
}

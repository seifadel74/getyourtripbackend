<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if admin user already exists
        if (User::where('username', 'admin')->exists()) {
            $this->command->warn('Admin user already exists!');
            return;
        }

        User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'), // Change this password!
        ]);

        $this->command->info('Admin user created successfully!');
        $this->command->warn('Default password is: admin123');
        $this->command->warn('Please change the password after first login!');
    }
}


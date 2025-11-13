<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-admin 
                            {--name=Admin : User name}
                            {--username=admin : Username (must be "admin" for admin privileges)}
                            {--email=admin@example.com : Email address}
                            {--password= : Password (will prompt if not provided)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an admin user with username';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->option('name');
        $username = $this->option('username');
        $email = $this->option('email');
        $password = $this->option('password');

        // Check if user already exists
        if (User::where('email', $email)->exists()) {
            $this->error("User with email {$email} already exists!");
            return 1;
        }

        if (User::where('username', $username)->exists()) {
            $this->error("User with username {$username} already exists!");
            return 1;
        }

        // Prompt for password if not provided
        if (!$password) {
            $password = $this->secret('Enter password (min 8 characters):');
            $passwordConfirm = $this->secret('Confirm password:');
            
            if ($password !== $passwordConfirm) {
                $this->error('Passwords do not match!');
                return 1;
            }

            if (strlen($password) < 8) {
                $this->error('Password must be at least 8 characters!');
                return 1;
            }
        }

        // Create the user
        $user = User::create([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info("User created successfully!");
        $this->line("Name: {$user->name}");
        $this->line("Username: {$user->username}");
        $this->line("Email: {$user->email}");
        $this->line("Is Admin: " . ($user->isAdmin() ? 'Yes' : 'No'));

        return 0;
    }
}


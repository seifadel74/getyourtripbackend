<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AddUsernameToExistingUsers extends Seeder
{
    /**
     * Run the database seeds.
     * This seeder adds usernames to existing users who don't have one.
     */
    public function run(): void
    {
        $usersWithoutUsername = User::whereNull('username')->get();

        foreach ($usersWithoutUsername as $user) {
            // Generate a username from email or name
            $baseUsername = $user->email 
                ? explode('@', $user->email)[0] 
                : Str::slug($user->name);
            
            // Make sure username is unique
            $username = $baseUsername;
            $counter = 1;
            while (User::where('username', $username)->exists()) {
                $username = $baseUsername . $counter;
                $counter++;
            }

            $user->username = $username;
            $user->save();

            $this->command->info("Added username '{$username}' to user: {$user->name} ({$user->email})");
        }

        $this->command->info('Finished adding usernames to existing users.');
    }
}


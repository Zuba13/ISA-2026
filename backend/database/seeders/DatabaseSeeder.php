<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a specific test user
        User::factory()
            ->has(\App\Models\Video::factory()->count(5))
            ->create([
                'name' => 'Test User',
                'surname' => 'Userovic',
                'username' => 'testuser',
                'address' => 'Test Address 123',
                'email' => 'test@example.com',
            ]);

        // Create 10 random users with 3 videos each
        User::factory(10)
            ->has(\App\Models\Video::factory()->count(3))
            ->create();
    }
}

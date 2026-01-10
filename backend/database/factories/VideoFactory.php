<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Video>
 */
class VideoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'thumbnail_path' => fake()->imageUrl(640, 480, 'nature'),
            'video_path' => 'https://www.w3schools.com/html/mov_bbb.mp4',
            'views' => fake()->numberBetween(0, 1000000),
            'user_id' => \App\Models\User::factory(),
        ];
    }
}

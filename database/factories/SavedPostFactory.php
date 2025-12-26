<?php

namespace Database\Factories;

use App\Models\SavedPost;
use App\Models\User;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<SavedPost>
 */
class SavedPostFactory extends Factory
{
    protected $model = SavedPost::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'post_id' => Post::factory(),
            'saved_at' => now(),
        ];
    }
}
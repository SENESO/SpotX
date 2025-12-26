<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\SavedPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SavedPostTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_a_post()
    {
        $user = User::factory()->create();
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/posts/{$post->id}/save");

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Post saved successfully',
            ]);

        $this->assertDatabaseHas('saved_posts', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
    }

    public function test_user_cannot_save_own_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/posts/{$post->id}/save");

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'You cannot save your own post',
            ]);
    }

    public function test_user_cannot_save_same_post_twice()
    {
        $user = User::factory()->create();
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        Sanctum::actingAs($user);

        // Save once
        $this->postJson("/api/posts/{$post->id}/save");

        // Try to save again
        $response = $this->postJson("/api/posts/{$post->id}/save");

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Post already saved',
            ]);
    }

    public function test_user_can_unsave_a_post()
    {
        $user = User::factory()->create();
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        SavedPost::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'saved_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/posts/{$post->id}/save");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Post unsaved successfully',
            ]);

        $this->assertDatabaseMissing('saved_posts', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
    }

    public function test_user_can_check_if_post_is_saved()
    {
        $user = User::factory()->create();
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        Sanctum::actingAs($user);

        // Initially not saved
        $response = $this->getJson("/api/posts/{$post->id}/saved");

        $response->assertStatus(200)
            ->assertJson([
                'saved' => false,
            ]);

        // Save the post
        $this->postJson("/api/posts/{$post->id}/save");

        // Now should be saved
        $response = $this->getJson("/api/posts/{$post->id}/saved");

        $response->assertStatus(200)
            ->assertJson([
                'saved' => true,
            ]);
    }

    public function test_user_can_view_saved_posts()
    {
        $user = User::factory()->create();
        $author = User::factory()->create();
        
        $posts = Post::factory()->count(5)->create(['user_id' => $author->id]);
        
        foreach ($posts as $post) {
            SavedPost::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'saved_at' => now()->subMinutes($post->id),
            ]);
        }

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/users/{$user->id}/saved-posts");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'pagination' => [
                    'current_page',
                    'next_page_url',
                    'prev_page_url',
                ],
            ])
            ->assertJsonCount(5, 'data');
    }

    public function test_unauthenticated_user_cannot_access_saved_posts()
    {
        $response = $this->postJson('/api/posts/1/save');

        $response->assertStatus(401);
    }

    public function test_unsaved_post_returns_404_for_unsave()
    {
        $user = User::factory()->create();
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/posts/{$post->id}/save");

        $response->assertStatus(404);
    }

    public function test_saved_posts_are_ordered_by_saved_at_desc()
    {
        $user = User::factory()->create();
        $author = User::factory()->create();
        
        $post1 = Post::factory()->create(['user_id' => $author->id]);
        $post2 = Post::factory()->create(['user_id' => $author->id]);
        $post3 = Post::factory()->create(['user_id' => $author->id]);
        
        SavedPost::create([
            'user_id' => $user->id,
            'post_id' => $post1->id,
            'saved_at' => now()->subDays(2),
        ]);
        
        SavedPost::create([
            'user_id' => $user->id,
            'post_id' => $post2->id,
            'saved_at' => now()->subDay(),
        ]);
        
        SavedPost::create([
            'user_id' => $user->id,
            'post_id' => $post3->id,
            'saved_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/users/{$user->id}/saved-posts");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
            
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $expectedOrder = [$post3->id, $post2->id, $post1->id];
        
        $this->assertEquals($expectedOrder, $ids);
    }
}
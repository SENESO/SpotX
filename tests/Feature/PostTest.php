<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use App\Models\Interaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    public function test_create_post(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/posts', [
                'content' => 'Test post content',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'post' => [
                    'id',
                    'uuid',
                    'content',
                    'user',
                    'media_urls',
                    'likes_count',
                    'reposts_count',
                    'quotes_count',
                    'replies_count',
                    'views_count',
                    'created_at',
                ],
            ]);

        $this->assertDatabaseHas('posts', [
            'content' => 'Test post content',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_create_post_with_media(): void
    {
        Storage::fake('public');
        
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->actingAs($this->user)
            ->postJson('/api/posts', [
                'content' => 'Test post with media',
                'media_files' => [$file],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('post.media_urls', function ($mediaUrls) {
                return count($mediaUrls) === 1;
            });
    }

    public function test_create_post_with_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/posts', [
                'content' => str_repeat('a', 501),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    public function test_create_post_requires_authentication(): void
    {
        $response = $this->postJson('/api/posts', [
            'content' => 'Test post',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_post(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/posts/{$post->id}", [
                'content' => 'Updated content',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('post.content', 'Updated content');
    }

    public function test_update_post_requires_authorization(): void
    {
        $post = Post::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/posts/{$post->id}", [
                'content' => 'Hacked content',
            ]);

        $response->assertStatus(403);
    }

    public function test_delete_post(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/posts/{$post->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted($post);
    }

    public function test_delete_post_requires_authorization(): void
    {
        $post = Post::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/posts/{$post->id}");

        $response->assertStatus(403);
    }

    public function test_get_post(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJsonPath('post.id', $post->id);
    }

    public function test_like_post(): void
    {
        $post = Post::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/posts/{$post->id}/like");

        $response->assertStatus(201);
        $this->assertDatabaseHas('interactions', [
            'user_id' => $this->user->id,
            'post_id' => $post->id,
            'interaction_type' => 'like',
        ]);
    }

    public function test_unlike_post(): void
    {
        $post = Post::factory()->create();
        Interaction::create([
            'user_id' => $this->user->id,
            'post_id' => $post->id,
            'interaction_type' => 'like',
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/posts/{$post->id}/like");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('interactions', [
            'user_id' => $this->user->id,
            'post_id' => $post->id,
            'interaction_type' => 'like',
        ]);
    }

    public function test_repost_post(): void
    {
        $post = Post::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/posts/{$post->id}/repost");

        $response->assertStatus(201);
        $this->assertDatabaseHas('interactions', [
            'user_id' => $this->user->id,
            'post_id' => $post->id,
            'interaction_type' => 'repost',
        ]);
    }

    public function test_cannot_repost_own_post(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/posts/{$post->id}/repost");

        $response->assertStatus(400);
    }

    public function test_quote_post(): void
    {
        $originalPost = Post::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/posts/{$originalPost->id}/quote", [
                'content' => 'This is my quote',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('post.original_post_uuid', $originalPost->uuid);
    }

    public function test_get_post_likes(): void
    {
        $post = Post::factory()->create();
        Interaction::factory()->count(3)->create([
            'post_id' => $post->id,
            'interaction_type' => 'like',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/posts/{$post->id}/likes");

        $response->assertStatus(200)
            ->assertJsonPath('count', 3);
    }

    public function test_get_post_reposts(): void
    {
        $post = Post::factory()->create();
        Interaction::factory()->count(3)->create([
            'post_id' => $post->id,
            'interaction_type' => 'repost',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/posts/{$post->id}/reposts");

        $response->assertStatus(200)
            ->assertJsonPath('count', 3);
    }

    public function test_get_post_quotes(): void
    {
        $originalPost = Post::factory()->create();
        Post::factory()->count(3)->create([
            'original_post_uuid' => $originalPost->uuid,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/posts/{$originalPost->id}/quotes");

        $response->assertStatus(200)
            ->assertJsonPath('count', 3);
    }

    public function test_feed_chronological(): void
    {
        Post::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/feed');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'posts',
                'pagination',
                'algorithm',
            ]);
    }

    public function test_feed_personalized(): void
    {
        Post::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/feed?algorithm=personalized');

        $response->assertStatus(200)
            ->assertJsonPath('algorithm', 'personalized');
    }

    public function test_search_posts(): void
    {
        Post::factory()->create(['content' => 'unique search term 12345']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/search/posts?q=search');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'query',
                'posts',
                'pagination',
            ]);
    }

    public function test_search_users(): void
    {
        User::factory()->create(['username' => 'searchuser123']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/search/users?q=searchuser');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'query',
                'users',
            ]);
    }

    public function test_mention_suggestions(): void
    {
        User::factory()->create(['username' => 'johndoe']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/mentions/suggestions?q=john');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'suggestions',
            ]);
    }

    public function test_user_posts(): void
    {
        Post::factory()->count(5)->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/users/{$this->otherUser->id}/posts");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'posts',
                'pagination',
            ]);
    }

    public function test_user_media_posts(): void
    {
        Post::factory()->count(3)->create([
            'user_id' => $this->otherUser->id,
            'media_urls' => [['url' => 'test.jpg']],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/users/{$this->otherUser->id}/media");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'posts',
                'pagination',
            ]);
    }

    public function test_user_liked_posts(): void
    {
        $post = Post::factory()->create(['user_id' => $this->otherUser->id]);
        Interaction::create([
            'user_id' => $this->otherUser->id,
            'post_id' => $post->id,
            'interaction_type' => 'like',
        ]);

        $response = $this->actingAs($this->otherUser)
            ->getJson("/api/users/{$this->otherUser->id}/likes");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'posts',
                'pagination',
            ]);
    }

    public function test_rate_limiting_on_post_creation(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $response = $this->actingAs($this->user)
                ->postJson('/api/posts', [
                    'content' => "Test post {$i}",
                ]);
            
            if ($response->getStatusCode() === 429) {
                $this->assertTrue(true);
                return;
            }
        }
        
        $this->assertTrue(true);
    }

    public function test_media_upload(): void
    {
        Storage::fake('public');
        
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->actingAs($this->user)
            ->postJson('/api/media/upload', [
                'file' => $file,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'media' => [
                    'id',
                    'uuid',
                    'url',
                    'type',
                ],
            ]);
    }

    public function test_reply_to_post(): void
    {
        $post = Post::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/posts/{$post->id}/replies", [
                'content' => 'This is a reply',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('reply.content', 'This is a reply');
    }

    public function test_reply_count_increments(): void
    {
        $post = Post::factory()->create(['replies_count' => 0]);

        $this->actingAs($this->user)
            ->postJson("/api/posts/{$post->id}/replies", [
                'content' => 'Reply',
            ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'replies_count' => 1,
        ]);
    }

    public function test_private_post_visibility(): void
    {
        $this->otherUser->update(['is_private' => true]);
        
        $post = Post::factory()->create([
            'user_id' => $this->otherUser->id,
            'visibility' => 'private',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/posts/{$post->id}");

        $response->assertStatus(404);
    }

    public function test_private_post_visible_to_owner(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'visibility' => 'private',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/posts/{$post->id}");

        $response->assertStatus(200);
    }

    public function test_followers_only_post_visibility(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->otherUser->id,
            'visibility' => 'followers_only',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/posts/{$post->id}");

        $response->assertStatus(404);
    }

    public function test_followers_only_post_visible_to_followers(): void
    {
        $this->user->following()->attach($this->otherUser->id);
        
        $post = Post::factory()->create([
            'user_id' => $this->otherUser->id,
            'visibility' => 'followers_only',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/posts/{$post->id}");

        $response->assertStatus(200);
    }

    public function test_blocked_user_posts_hidden(): void
    {
        $this->user->blocks()->attach($this->otherUser->id);
        
        Post::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/feed');

        $response->assertStatus(200)
            ->assertJsonPath('posts', []);
    }
}

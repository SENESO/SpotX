<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use App\Models\Notification;
use App\Models\NotificationSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $actor;
    protected Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actor = User::factory()->create();
        $this->post = Post::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_get_notifications(): void
    {
        Notification::factory()->count(5)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'notifications',
                'unread_count',
                'unread_counts_by_type',
            ]);
    }

    public function test_get_grouped_notifications(): void
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'type' => 'like',
            'related_post_id' => $this->post->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications?grouped=true');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'notifications',
                'unread_count',
                'unread_counts_by_type',
            ]);
    }

    public function test_get_single_notification(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'actor_id' => $this->actor->id,
            'type' => 'like',
            'related_post_id' => $this->post->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/notifications/{$notification->id}");

        $response->assertStatus(200)
            ->assertJsonPath('notification.id', $notification->id)
            ->assertJsonPath('notification.is_read', false);
    }

    public function test_mark_notification_as_read(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(200);
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'is_read' => true,
        ]);
    }

    public function test_mark_all_as_read(): void
    {
        Notification::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/notifications/mark-all-read');

        $response->assertStatus(200)
            ->assertJsonPath('count', 5);
        
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);
    }

    public function test_mark_multiple_as_read(): void
    {
        $notifications = Notification::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);

        $ids = $notifications->pluck('id')->take(3)->toArray();

        $response = $this->actingAs($this->user)
            ->postJson('/api/notifications/mark-multiple-read', [
                'notification_ids' => $ids,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('count', 3);
    }

    public function test_delete_notification(): void
    {
        $notification = Notification::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/notifications/{$notification->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
        ]);
    }

    public function test_clear_all_notifications(): void
    {
        Notification::factory()->count(10)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson('/api/notifications/clear-all');

        $response->assertStatus(200)
            ->assertJsonPath('count', 10);
        
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_get_unread_count(): void
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);
        Notification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'is_read' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications/unread-count');

        $response->assertStatus(200)
            ->assertJsonPath('unread_count', 3)
            ->assertJsonStructure([
                'unread_count',
                'unread_counts_by_type',
            ]);
    }

    public function test_notification_settings_index(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/notification-settings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'settings' => [
                    'notify_likes',
                    'notify_reposts',
                    'notify_quotes',
                    'notify_replies',
                    'notify_mentions',
                    'notify_follows',
                    'notify_follower_posts',
                    'quiet_hours_enabled',
                    'quiet_hours_start',
                    'quiet_hours_end',
                    'email_digest_enabled',
                    'email_digest_frequency',
                ],
            ]);
    }

    public function test_update_notification_settings(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson('/api/notification-settings', [
                'notify_likes' => false,
                'notify_reposts' => false,
                'email_digest_frequency' => 'never',
            ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('notification_settings', [
            'user_id' => $this->user->id,
            'notify_likes' => false,
            'notify_reposts' => false,
            'email_digest_frequency' => 'never',
        ]);
    }

    public function test_update_push_settings(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson('/api/notification-settings/push', [
                'notify_likes' => false,
                'notify_mentions' => false,
            ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('notification_settings', [
            'user_id' => $this->user->id,
            'notify_likes' => false,
            'notify_mentions' => false,
        ]);
    }

    public function test_update_quiet_hours(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson('/api/notification-settings/quiet-hours', [
                'quiet_hours_enabled' => true,
                'quiet_hours_start' => '22:00:00',
                'quiet_hours_end' => '08:00:00',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('quiet_hours.enabled', true)
            ->assertJsonPath('quiet_hours.is_currently_active', true);
        
        $this->assertDatabaseHas('notification_settings', [
            'user_id' => $this->user->id,
            'quiet_hours_enabled' => true,
        ]);
    }

    public function test_update_email_settings(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson('/api/notification-settings/email', [
                'email_digest_enabled' => true,
                'email_digest_frequency' => 'weekly',
            ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('notification_settings', [
            'user_id' => $this->user->id,
            'email_digest_enabled' => true,
            'email_digest_frequency' => 'weekly',
        ]);
    }

    public function test_cannot_view_other_users_notifications(): void
    {
        $notification = Notification::factory()->create(['user_id' => $this->actor->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/notifications/{$notification->id}");

        $response->assertStatus(404);
    }

    public function test_cannot_delete_other_users_notifications(): void
    {
        $notification = Notification::factory()->create(['user_id' => $this->actor->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/notifications/{$notification->id}");

        $response->assertStatus(404);
    }

    public function test_filter_notifications_by_type(): void
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'type' => 'like',
        ]);
        Notification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'type' => 'follow',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications?type=like');

        $response->assertStatus(200);
        $data = $response->json();
        
        foreach ($data['notifications'] as $notification) {
            $this->assertEquals('like', $notification['type']);
        }
    }

    public function test_notification_action_url(): void
    {
        $likeNotification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'like',
            'related_post_id' => $this->post->id,
        ]);

        $this->assertEquals("/posts/{$this->post->id}", $likeNotification->getActionUrl());

        $replyNotification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'reply',
            'related_post_id' => $this->post->id,
        ]);

        $this->assertEquals("/posts/{$this->post->id}", $replyNotification->getActionUrl());
    }

    public function test_notification_preview_text(): void
    {
        $likeNotification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'like',
        ]);

        $this->assertEquals('liked your post', $likeNotification->getPreviewText());

        $followNotification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'follow',
        ]);

        $this->assertEquals('started following you', $followNotification->getPreviewText());
    }

    public function test_notification_grouping(): void
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'type' => 'like',
            'related_post_id' => $this->post->id,
            'actor_id' => $this->actor->id,
        ]);

        $grouped = $this->user->notifications->groupBy(function ($n) {
            return $n->type . '_' . $n->related_post_id;
        });

        $this->assertEquals(1, $grouped->count());
    }
}

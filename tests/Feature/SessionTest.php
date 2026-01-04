<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Models\LiminSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

final class SessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_start_session(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/session/start');

        $response->assertStatus(201)
            ->assertJsonStructure(['session_id']);

        $this->assertDatabaseHas('limin_sessions', [
            'user_id' => $user->id,
            'stopped_at' => null,
        ]);
    }

    public function test_start_session_with_device_id(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/session/start', [
                'device_id' => 'macbook-pro-2024',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('limin_sessions', [
            'user_id' => $user->id,
            'device_id' => 'macbook-pro-2024',
            'stopped_at' => null,
        ]);
    }

    public function test_starting_new_session_stops_existing_session(): void
    {
        $user = User::factory()->create();

        // 最初のセッション開始
        $response1 = $this->actingAs($user)
            ->postJson('/api/session/start');

        $response1->assertStatus(201);
        $sessionId1 = $response1->json('session_id');

        // 2つ目のセッション開始
        $response2 = $this->actingAs($user)
            ->postJson('/api/session/start');

        $response2->assertStatus(201);
        $sessionId2 = $response2->json('session_id');

        $this->assertNotEquals($sessionId1, $sessionId2);

        // 最初のセッションは終了されている
        $this->assertDatabaseMissing('limin_sessions', [
            'id' => $sessionId1,
            'stopped_at' => null,
        ]);

        // 2つ目のセッションはアクティブ
        $this->assertDatabaseHas('limin_sessions', [
            'id' => $sessionId2,
            'stopped_at' => null,
        ]);
    }

    public function test_authenticated_user_can_stop_session(): void
    {
        $user = User::factory()->create();

        // セッション開始
        $startResponse = $this->actingAs($user)
            ->postJson('/api/session/start');

        $sessionId = $startResponse->json('session_id');

        // セッション終了
        $stopResponse = $this->actingAs($user)
            ->postJson('/api/session/stop');

        $stopResponse->assertStatus(204);

        $this->assertDatabaseMissing('limin_sessions', [
            'id' => $sessionId,
            'stopped_at' => null,
        ]);
    }

    public function test_stop_session_returns_404_when_no_active_session(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/session/stop');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'No active session found.',
            ]);
    }

    public function test_start_session_requires_authentication(): void
    {
        $response = $this->postJson('/api/session/start');

        $response->assertStatus(401);
    }

    public function test_stop_session_requires_authentication(): void
    {
        $response = $this->postJson('/api/session/stop');

        $response->assertStatus(401);
    }

    public function test_session_timeout_command_stops_old_sessions(): void
    {
        $user = User::factory()->create();

        // 25時間前に開始されたセッションを作成
        $oldSession = LiminSession::create([
            'id' => fake()->uuid(),
            'user_id' => $user->id,
            'started_at' => now()->subHours(25),
            'stopped_at' => null,
        ]);

        // 1時間前に開始されたセッションを作成（タイムアウト対象外）
        $recentSession = LiminSession::create([
            'id' => fake()->uuid(),
            'user_id' => $user->id,
            'started_at' => now()->subHour(),
            'stopped_at' => null,
        ]);

        // コマンド実行
        Artisan::call('session:timeout');

        // 古いセッションは終了されている
        $this->assertDatabaseMissing('limin_sessions', [
            'id' => $oldSession->id,
            'stopped_at' => null,
        ]);

        // 新しいセッションはまだアクティブ
        $this->assertDatabaseHas('limin_sessions', [
            'id' => $recentSession->id,
            'stopped_at' => null,
        ]);
    }

    public function test_device_id_max_length_validation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/session/start', [
                'device_id' => str_repeat('a', 101),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['device_id']);
    }
}

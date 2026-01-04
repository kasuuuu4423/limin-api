<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Models\Item;
use App\Infrastructure\Models\LiminSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class NextTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private LiminSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // アクティブセッションを作成
        $this->session = LiminSession::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'started_at' => now(),
            'stopped_at' => null,
        ]);
    }

    public function test_get_next_returns_204_when_no_items(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/next');

        $response->assertStatus(204);
    }

    public function test_get_next_returns_item_with_next_action(): void
    {
        $item = Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'next_action' => '企画書の目次を書く',
            'meta' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/next');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $item->id,
                'next_action' => '企画書の目次を書く',
                'type' => 'task',
                'meta' => false,
            ])
            ->assertJsonMissing(['is_interrupt']);
    }

    public function test_get_next_returns_item_in_fifo_order(): void
    {
        $item1 = Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'next_action' => '最初のタスク',
            'meta' => false,
            'created_at' => now()->subMinutes(10),
        ]);

        $item2 = Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'next_action' => '2番目のタスク',
            'meta' => false,
            'created_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/next');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $item1->id,
                'next_action' => '最初のタスク',
            ]);
    }

    public function test_get_next_excludes_completed_items(): void
    {
        Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'next_action' => '完了済みタスク',
            'meta' => false,
            'done_at' => now(),
        ]);

        $activeItem = Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'next_action' => '未完了タスク',
            'meta' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/next');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $activeItem->id,
            ]);
    }

    public function test_get_next_excludes_items_without_next_action(): void
    {
        Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'next_action' => '',
            'meta' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/next');

        $response->assertStatus(204);
    }

    public function test_get_next_excludes_items_with_availability_later(): void
    {
        Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'LATER',
            'next_action' => '後でやるタスク',
            'meta' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/next');

        $response->assertStatus(204);
    }

    public function test_get_next_excludes_items_with_state_wait(): void
    {
        Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'WAIT',
            'availability' => 'NOW',
            'next_action' => '待機中タスク',
            'meta' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/next');

        $response->assertStatus(204);
    }

    public function test_get_next_returns_deadline_interrupt_when_due_within_48_hours(): void
    {
        // 締切が近いItem
        $urgentItem = Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'next_action' => '緊急タスク',
            'meta' => false,
            'due_at' => now()->addHours(24),
            'created_at' => now()->subMinutes(5),
        ]);

        // 普通のItem（先に作成）
        $normalItem = Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'next_action' => '通常タスク',
            'meta' => false,
            'created_at' => now()->subMinutes(10),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/next');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $urgentItem->id,
                'next_action' => '緊急タスク',
                'is_interrupt' => true,
            ]);
    }

    public function test_deadline_interrupt_is_offered_only_once_per_session(): void
    {
        // 締切が近いItem
        $urgentItem = Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'next_action' => '緊急タスク',
            'meta' => false,
            'due_at' => now()->addHours(24),
        ]);

        // 普通のItem
        $normalItem = Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'next_action' => '通常タスク',
            'meta' => false,
            'created_at' => now()->subMinutes(10),
        ]);

        // 1回目: 締切割り込みが返される
        $response1 = $this->actingAs($this->user)
            ->getJson('/api/next');

        $response1->assertStatus(200)
            ->assertJson(['is_interrupt' => true]);

        // セッションのinterrupt_offered_atが設定されていることを確認
        $this->assertDatabaseMissing('limin_sessions', [
            'id' => $this->session->id,
            'interrupt_offered_at' => null,
        ]);

        // 2回目: 通常レーンのItemが返される（締切割り込みではない）
        $response2 = $this->actingAs($this->user)
            ->getJson('/api/next');

        $response2->assertStatus(200)
            ->assertJsonMissing(['is_interrupt']);
    }

    public function test_accept_interrupt_returns_204(): void
    {
        $urgentItem = Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'next_action' => '緊急タスク',
            'meta' => false,
            'due_at' => now()->addHours(24),
        ]);

        // 締切割り込みを取得
        $this->actingAs($this->user)
            ->getJson('/api/next');

        // 採用
        $response = $this->actingAs($this->user)
            ->postJson('/api/next/interrupt/accept');

        $response->assertStatus(204);

        // interrupt_accepted_atが設定されている
        $this->assertDatabaseMissing('limin_sessions', [
            'id' => $this->session->id,
            'interrupt_accepted_at' => null,
        ]);
    }

    public function test_accept_interrupt_returns_409_when_no_interrupt_offered(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/next/interrupt/accept');

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'No interrupt offer pending.',
            ]);
    }

    public function test_reject_interrupt_returns_normal_item(): void
    {
        $urgentItem = Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'next_action' => '緊急タスク',
            'meta' => false,
            'due_at' => now()->addHours(24),
            'created_at' => now()->subMinutes(5),
        ]);

        $normalItem = Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'next_action' => '通常タスク',
            'meta' => false,
            'created_at' => now()->subMinutes(10),
        ]);

        // 締切割り込みを取得
        $this->actingAs($this->user)
            ->getJson('/api/next');

        // 却下
        $response = $this->actingAs($this->user)
            ->postJson('/api/next/interrupt/reject');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $normalItem->id,
                'next_action' => '通常タスク',
            ])
            ->assertJsonMissing(['is_interrupt']);
    }

    public function test_reject_interrupt_returns_204_when_no_normal_items(): void
    {
        $urgentItem = Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'next_action' => '緊急タスク',
            'meta' => false,
            'due_at' => now()->addHours(24),
        ]);

        // 締切割り込みを取得
        $this->actingAs($this->user)
            ->getJson('/api/next');

        // 却下
        $response = $this->actingAs($this->user)
            ->postJson('/api/next/interrupt/reject');

        $response->assertStatus(204);
    }

    public function test_reject_interrupt_returns_409_when_no_interrupt_offered(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/next/interrupt/reject');

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'No interrupt offer pending.',
            ]);
    }

    public function test_get_next_requires_active_session(): void
    {
        // セッションを終了
        $this->session->update(['stopped_at' => now()]);

        Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'next_action' => 'タスク',
            'meta' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/next');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'No active session. Please start a session first.',
            ]);
    }

    public function test_get_next_requires_authentication(): void
    {
        $response = $this->getJson('/api/next');

        $response->assertStatus(401);
    }

    public function test_accept_interrupt_requires_authentication(): void
    {
        $response = $this->postJson('/api/next/interrupt/accept');

        $response->assertStatus(401);
    }

    public function test_reject_interrupt_requires_authentication(): void
    {
        $response = $this->postJson('/api/next/interrupt/reject');

        $response->assertStatus(401);
    }

    public function test_get_next_updates_last_presented_at(): void
    {
        $item = Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'next_action' => 'タスク',
            'meta' => false,
            'last_presented_at' => null,
        ]);

        $this->actingAs($this->user)
            ->getJson('/api/next');

        $item->refresh();
        $this->assertNotNull($item->last_presented_at);
    }

    public function test_get_next_updates_session_current_item(): void
    {
        $item = Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'next_action' => 'タスク',
            'meta' => false,
        ]);

        $this->actingAs($this->user)
            ->getJson('/api/next');

        $this->assertDatabaseHas('limin_sessions', [
            'id' => $this->session->id,
            'current_item_id' => $item->id,
        ]);
    }

    public function test_session_suppression_excludes_deferred_items(): void
    {
        // セッション開始後に提示され、availabilityがLATERになったItem
        $deferredItem = Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'next_action' => '先送りされたタスク',
            'meta' => false,
            'last_presented_at' => now()->addMinutes(1),
            'created_at' => now()->subMinutes(20),
        ]);

        // セッション開始前に提示されたItem
        $activeItem = Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'next_action' => 'アクティブタスク',
            'meta' => false,
            'last_presented_at' => now()->subHours(2),
            'created_at' => now()->subMinutes(10),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/next');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $activeItem->id,
            ]);
    }
}

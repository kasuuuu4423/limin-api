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
            'title' => 'A 企画書',
            'next_action' => '目次を書く',
            'meta' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/next');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $item->id,
                'title' => 'A 企画書',
                'next_action' => '目次を書く',
                'type' => 'task',
                'meta' => false,
            ])
            ->assertJsonMissing(['is_interrupt', 'needs_first_action']);
    }

    public function test_get_next_returns_one_of_eligible_items_randomly(): void
    {
        $item1 = Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'title' => '最初のタスク',
            'next_action' => '最初の一手',
            'meta' => false,
            'created_at' => now()->subMinutes(10),
        ]);

        $item2 = Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'title' => '2番目のタスク',
            'next_action' => '2番目の一手',
            'meta' => false,
            'created_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/next');

        $response->assertStatus(200);

        // ランダム選定なので、どちらかのItemが返される
        $returnedId = $response->json('id');
        $this->assertContains($returnedId, [$item1->id, $item2->id]);
    }

    public function test_get_next_excludes_completed_items(): void
    {
        Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'title' => '完了済みタスク',
            'next_action' => '完了済みの一手',
            'meta' => false,
            'done_at' => now(),
        ]);

        $activeItem = Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'title' => '未完了タスク',
            'next_action' => '未完了の一手',
            'meta' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/next');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $activeItem->id,
            ]);
    }

    public function test_get_next_excludes_items_with_availability_later(): void
    {
        Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'LATER',
            'title' => '後でやるタスク',
            'next_action' => '後での一手',
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
            'title' => '待機中タスク',
            'next_action' => '待機中の一手',
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
            'title' => '緊急タスク',
            'next_action' => '緊急の一手',
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
            'title' => '通常タスク',
            'next_action' => '通常の一手',
            'meta' => false,
            'created_at' => now()->subMinutes(10),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/next');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $urgentItem->id,
                'next_action' => '緊急の一手',
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
            'title' => '緊急タスク',
            'next_action' => '緊急の一手',
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
            'title' => '通常タスク',
            'next_action' => '通常の一手',
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
            'title' => '緊急タスク',
            'next_action' => '緊急の一手',
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
            'title' => '緊急タスク',
            'next_action' => '緊急の一手',
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
            'title' => '通常タスク',
            'next_action' => '通常の一手',
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
            ->assertJsonMissing(['is_interrupt']);

        // ランダム選定なので、どちらかの通常Itemが返される
        $returnedId = $response->json('id');
        $this->assertContains($returnedId, [$urgentItem->id, $normalItem->id]);
    }

    public function test_reject_interrupt_returns_204_when_no_normal_items(): void
    {
        $urgentItem = Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'title' => '緊急タスク',
            'next_action' => '緊急の一手',
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
            'title' => 'タスク',
            'next_action' => '一手',
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
            'title' => 'タスク',
            'next_action' => '一手',
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
            'title' => 'タスク',
            'next_action' => '一手',
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
            'title' => '先送りされたタスク',
            'next_action' => '先送りの一手',
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
            'title' => 'アクティブタスク',
            'next_action' => 'アクティブの一手',
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

    // ========================================
    // 選定ロジック重点テスト（フェーズ6追加分）
    // ========================================

    public function test_deadline_interrupt_selects_closest_deadline(): void
    {
        // 48時間後の締切
        $laterDeadlineItem = Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'title' => '後の締切タスク',
            'next_action' => '後の一手',
            'meta' => false,
            'due_at' => now()->addHours(47),
        ]);

        // 24時間後の締切（より近い）
        $closerDeadlineItem = Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'title' => '近い締切タスク',
            'next_action' => '近い一手',
            'meta' => false,
            'due_at' => now()->addHours(12),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/next');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $closerDeadlineItem->id,
                'is_interrupt' => true,
            ]);
    }

    public function test_get_next_excludes_deleted_items(): void
    {
        // 論理削除されたItem
        $deletedItem = Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'title' => '削除されたタスク',
            'next_action' => '削除の一手',
            'meta' => false,
        ]);
        $deletedItem->delete();

        // 有効なItem
        $activeItem = Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'title' => '有効なタスク',
            'next_action' => '有効な一手',
            'meta' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/next');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $activeItem->id,
            ]);
    }

    public function test_get_next_excludes_other_users_items(): void
    {
        $otherUser = User::factory()->create();

        // 他のユーザーのItem
        Item::create([
            'id' => fake()->uuid(),
            'user_id' => $otherUser->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'title' => '他ユーザーのタスク',
            'next_action' => '他の一手',
            'meta' => false,
        ]);

        // 自分のItemがない場合
        $response = $this->actingAs($this->user)
            ->getJson('/api/next');

        $response->assertStatus(204);
    }

    public function test_get_next_excludes_items_with_availability_blocked(): void
    {
        Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'BLOCKED',
            'title' => 'ブロック中タスク',
            'next_action' => 'ブロックの一手',
            'meta' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/next');

        $response->assertStatus(204);
    }

    public function test_deadline_interrupt_includes_past_deadlines(): void
    {
        // 過去の締切も割り込み対象（締切を過ぎたタスクも優先的に提示）
        $pastDeadlineItem = Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'title' => '過去の締切タスク',
            'next_action' => '過去の一手',
            'meta' => false,
            'due_at' => now()->subHours(1),
        ]);

        // 通常のItem
        Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'title' => '通常タスク',
            'next_action' => '通常の一手',
            'meta' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/next');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $pastDeadlineItem->id,
                'is_interrupt' => true,
            ]);
    }

    public function test_deadline_interrupt_excludes_deadlines_beyond_48_hours(): void
    {
        // 49時間後の締切は割り込み対象外
        Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'title' => '遠い締切タスク',
            'next_action' => '遠い一手',
            'meta' => false,
            'due_at' => now()->addHours(49),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/next');

        // 通常レーンとして返される（締切割り込みではない）
        $response->assertStatus(200)
            ->assertJsonMissing(['is_interrupt' => true]);
    }

    public function test_get_next_with_next_action_null_is_still_selected(): void
    {
        // next_actionがnullでも選定される（仕様: next_actionの有無は選定条件に含めない）
        $item = Item::create([
            'id' => fake()->uuid(),
            'user_id' => $this->user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'title' => 'タスク名のみ',
            'next_action' => null,
            'meta' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/next');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $item->id,
                'title' => 'タスク名のみ',
            ]);
    }

    public function test_accept_interrupt_requires_active_session(): void
    {
        // セッションを終了
        $this->session->update(['stopped_at' => now()]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/next/interrupt/accept');

        $response->assertStatus(403);
    }

    public function test_reject_interrupt_requires_active_session(): void
    {
        // セッションを終了
        $this->session->update(['stopped_at' => now()]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/next/interrupt/reject');

        $response->assertStatus(403);
    }
}

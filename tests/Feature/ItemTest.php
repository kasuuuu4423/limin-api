<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_delete_own_item(): void
    {
        $user = User::factory()->create();
        $item = Item::create([
            'user_id' => $user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'next_action' => 'テストタスク',
            'meta' => false,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/item/{$item->id}");

        $response->assertStatus(204);

        // 論理削除なので deleted_at が設定される
        $this->assertSoftDeleted('items', [
            'id' => $item->id,
        ]);
    }

    public function test_user_cannot_delete_other_users_item(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $item = Item::create([
            'user_id' => $otherUser->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'next_action' => '他のユーザーのタスク',
            'meta' => false,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/item/{$item->id}");

        $response->assertStatus(404);

        // 削除されていないことを確認
        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'deleted_at' => null,
        ]);
    }

    public function test_delete_returns_404_for_nonexistent_item(): void
    {
        $user = User::factory()->create();
        $nonexistentId = '00000000-0000-0000-0000-000000000000';

        $response = $this->actingAs($user)
            ->deleteJson("/api/item/{$nonexistentId}");

        $response->assertStatus(404)
            ->assertJson(['message' => 'Item not found.']);
    }

    public function test_delete_requires_authentication(): void
    {
        $user = User::factory()->create();
        $item = Item::create([
            'user_id' => $user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'next_action' => 'テストタスク',
            'meta' => false,
        ]);

        $response = $this->deleteJson("/api/item/{$item->id}");

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_update_next_action(): void
    {
        $user = User::factory()->create();
        $item = Item::create([
            'user_id' => $user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'next_action' => '元のタスク',
            'meta' => false,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/item/{$item->id}/next-action", [
                'next_action' => '更新後のタスク',
            ]);

        $response->assertStatus(204);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'next_action' => '更新後のタスク',
        ]);
    }

    public function test_user_cannot_update_other_users_item_next_action(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $item = Item::create([
            'user_id' => $otherUser->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'next_action' => '他のユーザーのタスク',
            'meta' => false,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/item/{$item->id}/next-action", [
                'next_action' => '更新しようとした',
            ]);

        $response->assertStatus(404);

        // 更新されていないことを確認
        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'next_action' => '他のユーザーのタスク',
        ]);
    }

    public function test_update_next_action_returns_404_for_nonexistent_item(): void
    {
        $user = User::factory()->create();
        $nonexistentId = '00000000-0000-0000-0000-000000000000';

        $response = $this->actingAs($user)
            ->postJson("/api/item/{$nonexistentId}/next-action", [
                'next_action' => '更新タスク',
            ]);

        $response->assertStatus(404)
            ->assertJson(['message' => 'Item not found.']);
    }

    public function test_update_next_action_requires_next_action(): void
    {
        $user = User::factory()->create();
        $item = Item::create([
            'user_id' => $user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'next_action' => '元のタスク',
            'meta' => false,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/item/{$item->id}/next-action", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['next_action']);
    }

    public function test_update_next_action_cannot_exceed_500_characters(): void
    {
        $user = User::factory()->create();
        $item = Item::create([
            'user_id' => $user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'next_action' => '元のタスク',
            'meta' => false,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/item/{$item->id}/next-action", [
                'next_action' => str_repeat('あ', 501),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['next_action']);
    }

    public function test_update_next_action_requires_authentication(): void
    {
        $user = User::factory()->create();
        $item = Item::create([
            'user_id' => $user->id,
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'next_action' => '元のタスク',
            'meta' => false,
        ]);

        $response = $this->postJson("/api/item/{$item->id}/next-action", [
            'next_action' => '更新タスク',
        ]);

        $response->assertStatus(401);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CaptureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_capture_item(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/capture', [
                'text' => '明日までに企画書を送る',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id']);

        $this->assertDatabaseHas('items', [
            'user_id' => $user->id,
            'next_action' => '明日までに企画書を送る',
            'type' => 'task',
            'state' => 'DO',
            'availability' => 'NOW',
            'meta' => false,
        ]);
    }

    public function test_capture_requires_text(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/capture', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['text']);
    }

    public function test_capture_text_cannot_exceed_500_characters(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/capture', [
                'text' => str_repeat('あ', 501),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['text']);
    }

    public function test_capture_requires_authentication(): void
    {
        $response = $this->postJson('/api/capture', [
            'text' => '企画書を送る',
        ]);

        $response->assertStatus(401);
    }

    public function test_captured_item_has_default_values(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/capture', [
                'text' => 'テストタスク',
            ]);

        $response->assertStatus(201);

        $item = Item::where('user_id', $user->id)->first();

        $this->assertNotNull($item);
        $this->assertEquals('task', $item->type);
        $this->assertEquals('DO', $item->state);
        $this->assertEquals('NOW', $item->availability);
        $this->assertFalse($item->meta);
        $this->assertNull($item->due_at);
        $this->assertNull($item->timebox);
        $this->assertNull($item->done_at);
    }
}

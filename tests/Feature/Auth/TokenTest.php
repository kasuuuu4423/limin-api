<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_token_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/auth/token', [
            'email' => 'test@example.com',
            'password' => 'password',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token']);
    }

    public function test_user_cannot_get_token_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/auth/token', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'The provided credentials are incorrect.']);
    }

    public function test_user_cannot_get_token_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/auth/token', [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(401);
    }

    public function test_token_request_requires_email(): void
    {
        $response = $this->postJson('/api/auth/token', [
            'password' => 'password',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_token_request_requires_password(): void
    {
        $response = $this->postJson('/api/auth/token', [
            'email' => 'test@example.com',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_token_request_requires_device_name(): void
    {
        $response = $this->postJson('/api/auth/token', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['device_name']);
    }

    public function test_old_tokens_are_deleted_when_limit_exceeded(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // 5つのトークンを作成
        for ($i = 1; $i <= 5; $i++) {
            $user->createToken("Device {$i}");
        }

        $this->assertCount(5, $user->tokens);

        // 6つ目のトークンを発行
        $response = $this->postJson('/api/auth/token', [
            'email' => 'test@example.com',
            'password' => 'password',
            'device_name' => 'Device 6',
        ]);

        $response->assertStatus(200);

        // リフレッシュして確認
        $user->refresh();
        $this->assertCount(5, $user->tokens);
    }
}

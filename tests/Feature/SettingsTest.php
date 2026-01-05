<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_settings(): void
    {
        $user = User::factory()->create([
            'later_restore_at' => '05:00:00',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/settings');

        $response->assertStatus(200)
            ->assertJson([
                'later_restore_at' => '05:00',
            ]);
    }

    public function test_get_settings_requires_authentication(): void
    {
        $response = $this->getJson('/api/settings');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_update_settings(): void
    {
        $user = User::factory()->create([
            'later_restore_at' => '05:00:00',
        ]);

        $response = $this->actingAs($user)
            ->patchJson('/api/settings', [
                'later_restore_at' => '06:30',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'later_restore_at' => '06:30',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'later_restore_at' => '06:30',
        ]);
    }

    public function test_update_settings_requires_authentication(): void
    {
        $response = $this->patchJson('/api/settings', [
            'later_restore_at' => '06:00',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_settings_validates_time_format(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patchJson('/api/settings', [
                'later_restore_at' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['later_restore_at']);
    }

    public function test_update_settings_allows_empty_body(): void
    {
        $user = User::factory()->create([
            'later_restore_at' => '05:00:00',
        ]);

        $response = $this->actingAs($user)
            ->patchJson('/api/settings', []);

        $response->assertStatus(200)
            ->assertJson([
                'later_restore_at' => '05:00',
            ]);
    }
}

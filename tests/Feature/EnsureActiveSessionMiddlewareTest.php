<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\EnsureActiveSession;
use App\Infrastructure\Models\LiminSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

final class EnsureActiveSessionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_middleware_allows_request_with_active_session(): void
    {
        $user = User::factory()->create();

        // アクティブセッションを作成
        LiminSession::create([
            'id' => fake()->uuid(),
            'user_id' => $user->id,
            'started_at' => now(),
            'stopped_at' => null,
        ]);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = app(EnsureActiveSession::class);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_middleware_rejects_request_without_active_session(): void
    {
        $user = User::factory()->create();

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = app(EnsureActiveSession::class);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('No active session', $response->getContent());
    }

    public function test_middleware_rejects_request_when_session_is_stopped(): void
    {
        $user = User::factory()->create();

        // 終了済みセッションを作成
        LiminSession::create([
            'id' => fake()->uuid(),
            'user_id' => $user->id,
            'started_at' => now()->subHour(),
            'stopped_at' => now(),
        ]);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = app(EnsureActiveSession::class);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_middleware_rejects_unauthenticated_request(): void
    {
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => null);

        $middleware = app(EnsureActiveSession::class);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
    }
}

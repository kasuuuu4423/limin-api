<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Domain\Limin\Repository\SessionRepositoryInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureActiveSession
{
    public function __construct(
        private readonly SessionRepositoryInterface $sessionRepository,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $session = $this->sessionRepository->findActiveByUserId($user->id);

        if ($session === null) {
            return response()->json([
                'message' => 'No active session. Please start a session first.',
            ], 403);
        }

        return $next($request);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StartSessionRequest;
use App\Models\User;
use App\UseCases\StartSessionUseCase;
use App\UseCases\StopSessionUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class SessionController extends Controller
{
    public function __construct(
        private readonly StartSessionUseCase $startSessionUseCase,
        private readonly StopSessionUseCase $stopSessionUseCase,
    ) {}

    /**
     * POST /session/start
     * セッション開始
     */
    public function start(StartSessionRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var array{device_id?: string|null} $validated */
        $validated = $request->validated();

        $sessionId = $this->startSessionUseCase->execute(
            userId: $user->id,
            deviceId: $validated['device_id'] ?? null,
        );

        return response()->json([
            'session_id' => $sessionId,
        ], 201);
    }

    /**
     * POST /session/stop
     * セッション終了
     */
    public function stop(Request $request): JsonResponse|Response
    {
        /** @var User $user */
        $user = $request->user();

        $stopped = $this->stopSessionUseCase->execute(
            userId: $user->id,
        );

        if (! $stopped) {
            return response()->json([
                'message' => 'No active session found.',
            ], 404);
        }

        return response()->noContent();
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SettingsController extends Controller
{
    /**
     * GET /settings
     * ユーザー設定取得
     */
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'later_restore_at' => $this->formatTime($user->later_restore_at),
        ]);
    }

    /**
     * PATCH /settings
     * ユーザー設定更新
     */
    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var array{later_restore_at?: string} $validated */
        $validated = $request->validated();

        if (isset($validated['later_restore_at'])) {
            $user->later_restore_at = $validated['later_restore_at'];
        }

        $user->save();

        return response()->json([
            'later_restore_at' => $this->formatTime($user->later_restore_at),
        ]);
    }

    /**
     * 時刻をHH:MM形式にフォーマット
     */
    private function formatTime(string $time): string
    {
        // DBから取得した時刻（HH:MM:SS or HH:MM）をHH:MM形式に
        return substr($time, 0, 5);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Auth\TokenRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

final class AuthController extends Controller
{
    /**
     * POST /auth/token
     * トークン発行
     */
    public function token(TokenRequest $request): JsonResponse
    {
        /** @var array{email: string, password: string, device_name: string} $validated */
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if ($user === null || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        // 古いトークンを削除（5件を超える場合）
        $tokenCount = $user->tokens()->count();
        if ($tokenCount >= 5) {
            $user->tokens()
                ->orderBy('created_at', 'asc')
                ->limit($tokenCount - 4)
                ->delete();
        }

        $token = $user->createToken($validated['device_name'])->plainTextToken;

        return response()->json([
            'token' => $token,
        ]);
    }

    /**
     * POST /auth/logout
     * トークン失効
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // 現在のトークンを削除
        $user->currentAccessToken()->delete();

        return response()->json(null, 204);
    }
}

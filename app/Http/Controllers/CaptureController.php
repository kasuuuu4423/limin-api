<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CaptureRequest;
use App\Models\User;
use App\UseCases\CaptureItemUseCase;
use Illuminate\Http\JsonResponse;

final class CaptureController extends Controller
{
    public function __construct(
        private readonly CaptureItemUseCase $captureItemUseCase,
    ) {}

    /**
     * POST /capture
     * 1行登録
     */
    public function store(CaptureRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var array{text: string} $validated */
        $validated = $request->validated();

        $id = $this->captureItemUseCase->execute(
            userId: $user->id,
            text: $validated['text'],
        );

        return response()->json([
            'id' => $id,
        ], 201);
    }
}

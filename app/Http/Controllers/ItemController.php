<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateNextActionRequest;
use App\Models\User;
use App\UseCases\DeleteItemUseCase;
use App\UseCases\UpdateNextActionUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ItemController extends Controller
{
    public function __construct(
        private readonly DeleteItemUseCase $deleteItemUseCase,
        private readonly UpdateNextActionUseCase $updateNextActionUseCase,
    ) {}

    /**
     * DELETE /item/{id}
     * Item削除（論理削除）
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $deleted = $this->deleteItemUseCase->execute(
            userId: $user->id,
            itemId: $id,
        );

        if (! $deleted) {
            return response()->json([
                'message' => 'Item not found.',
            ], 404);
        }

        return response()->json(null, 204);
    }

    /**
     * POST /item/{id}/next-action
     * nextAction更新
     */
    public function updateNextAction(UpdateNextActionRequest $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var array{next_action: string} $validated */
        $validated = $request->validated();

        $updated = $this->updateNextActionUseCase->execute(
            userId: $user->id,
            itemId: $id,
            nextAction: $validated['next_action'],
        );

        if (! $updated) {
            return response()->json([
                'message' => 'Item not found.',
            ], 404);
        }

        return response()->json(null, 204);
    }
}

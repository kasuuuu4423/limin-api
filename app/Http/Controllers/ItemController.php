<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateNextActionRequest;
use App\Models\User;
use App\UseCases\CompleteItemUseCase;
use App\UseCases\ContinueItemUseCase;
use App\UseCases\DeferItemUseCase;
use App\UseCases\DeleteItemUseCase;
use App\UseCases\UnblockItemsUseCase;
use App\UseCases\UpdateNextActionUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ItemController extends Controller
{
    public function __construct(
        private readonly DeleteItemUseCase $deleteItemUseCase,
        private readonly UpdateNextActionUseCase $updateNextActionUseCase,
        private readonly CompleteItemUseCase $completeItemUseCase,
        private readonly ContinueItemUseCase $continueItemUseCase,
        private readonly DeferItemUseCase $deferItemUseCase,
        private readonly UnblockItemsUseCase $unblockItemsUseCase,
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

    /**
     * POST /item/{id}/complete
     * タスク完了
     */
    public function complete(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $completed = $this->completeItemUseCase->execute(
            userId: $user->id,
            itemId: $id,
        );

        if (! $completed) {
            return response()->json([
                'message' => 'Item not found.',
            ], 404);
        }

        return response()->json(null, 204);
    }

    /**
     * POST /item/{id}/continue
     * 次の一手を設定して継続
     */
    public function continueItem(UpdateNextActionRequest $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var array{next_action: string} $validated */
        $validated = $request->validated();

        $continued = $this->continueItemUseCase->execute(
            userId: $user->id,
            itemId: $id,
            nextAction: $validated['next_action'],
        );

        if (! $continued) {
            return response()->json([
                'message' => 'Item not found.',
            ], 404);
        }

        return response()->json(null, 204);
    }

    /**
     * POST /item/{id}/defer
     * 今は無理（先送り）
     *
     * このItemについて今は気力がない → LATER固定
     */
    public function defer(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $deferred = $this->deferItemUseCase->execute(
            userId: $user->id,
            itemId: $id,
        );

        if (! $deferred) {
            return response()->json([
                'message' => 'Item not found.',
            ], 404);
        }

        return response()->json(null, 204);
    }

    /**
     * POST /items/unblock
     * BLOCKED一括解除
     *
     * availability = BLOCKED のItemをすべて NOW に変更する
     */
    public function unblock(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $count = $this->unblockItemsUseCase->execute(
            userId: $user->id,
        );

        return response()->json([
            'count' => $count,
        ]);
    }
}

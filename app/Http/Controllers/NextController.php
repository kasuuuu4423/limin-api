<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\UseCases\AcceptInterruptUseCase;
use App\UseCases\GetNextItemUseCase;
use App\UseCases\RejectInterruptUseCase;
use Domain\Limin\Entity\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class NextController extends Controller
{
    public function __construct(
        private readonly GetNextItemUseCase $getNextItemUseCase,
        private readonly AcceptInterruptUseCase $acceptInterruptUseCase,
        private readonly RejectInterruptUseCase $rejectInterruptUseCase,
    ) {}

    /**
     * GET /next
     * 次の1件を取得
     */
    public function show(Request $request): JsonResponse|Response
    {
        /** @var User $user */
        $user = $request->user();

        $result = $this->getNextItemUseCase->execute($user->id);

        if ($result->isEmpty || $result->item === null) {
            return response()->noContent();
        }

        return response()->json($this->formatItem($result->item, $result->isInterrupt));
    }

    /**
     * POST /next/interrupt/accept
     * 締切割り込みを採用
     */
    public function acceptInterrupt(Request $request): JsonResponse|Response
    {
        /** @var User $user */
        $user = $request->user();

        $success = $this->acceptInterruptUseCase->execute($user->id);

        if (! $success) {
            return response()->json([
                'message' => 'No interrupt offer pending.',
            ], 409);
        }

        return response()->noContent();
    }

    /**
     * POST /next/interrupt/reject
     * 締切割り込みを却下
     */
    public function rejectInterrupt(Request $request): JsonResponse|Response
    {
        /** @var User $user */
        $user = $request->user();

        $result = $this->rejectInterruptUseCase->execute($user->id);

        if (! $result['success']) {
            return response()->json([
                'message' => 'No interrupt offer pending.',
            ], 409);
        }

        if ($result['item'] === null) {
            return response()->noContent();
        }

        return response()->json($this->formatItem($result['item'], false));
    }

    /**
     * Itemをレスポンス形式に変換
     *
     * @return array<string, mixed>
     */
    private function formatItem(Item $item, bool $isInterrupt): array
    {
        $response = [
            'id' => $item->id,
            'next_action' => $item->nextAction->value,
            'due_at' => $item->dueAt?->format(\DateTimeInterface::ATOM),
            'timebox' => $item->timebox,
            'type' => $item->type->value,
            'meta' => $item->meta,
        ];

        if ($isInterrupt) {
            $response['is_interrupt'] = true;
        }

        return $response;
    }
}

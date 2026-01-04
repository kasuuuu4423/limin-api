<?php

declare(strict_types=1);

namespace App\UseCases;

use Domain\Limin\Repository\ItemRepositoryInterface;
use Domain\Limin\Repository\SessionRepositoryInterface;
use Domain\Limin\Service\ItemSelectionService;
use Domain\Limin\Service\SelectionResult;

final readonly class GetNextItemUseCase
{
    public function __construct(
        private SessionRepositoryInterface $sessionRepository,
        private ItemRepositoryInterface $itemRepository,
        private ItemSelectionService $selectionService,
    ) {}

    /**
     * 次のItemを取得する
     *
     * @return SelectionResult 選定結果
     *
     * @throws \RuntimeException セッションが見つからない場合
     */
    public function execute(int $userId): SelectionResult
    {
        $session = $this->sessionRepository->findActiveByUserId($userId);

        if ($session === null) {
            throw new \RuntimeException('No active session found.');
        }

        $result = $this->selectionService->selectNext($userId, $session);

        if ($result->isEmpty || $result->item === null) {
            return $result;
        }

        $now = new \DateTimeImmutable;
        $item = $result->item;

        // Item の last_presented_at を更新
        $this->itemRepository->updateLastPresentedAt($item->id, $now);

        // 締切割り込みの場合、interrupt_offered_at を記録
        if ($result->isInterrupt) {
            $this->sessionRepository->markInterruptOffered($session->id, $now);
        }

        // セッションの current_item を更新（通常レーンの場合のみ）
        // 締切割り込みの場合は採用・却下の判断後に更新
        if (! $result->isInterrupt) {
            $this->sessionRepository->updateCurrentItem($session->id, $item->id, $now);
        }

        return $result;
    }
}

<?php

declare(strict_types=1);

namespace App\UseCases;

use Domain\Limin\Entity\Item;
use Domain\Limin\Repository\ItemRepositoryInterface;
use Domain\Limin\Repository\SessionRepositoryInterface;
use Domain\Limin\Service\ItemSelectionService;

final readonly class RejectInterruptUseCase
{
    public function __construct(
        private SessionRepositoryInterface $sessionRepository,
        private ItemRepositoryInterface $itemRepository,
        private ItemSelectionService $selectionService,
    ) {}

    /**
     * 締切割り込みを却下し、通常レーンのItemを返す
     *
     * @return array{success: bool, item: ?Item} 成功フラグと通常レーンのItem
     */
    public function execute(int $userId): array
    {
        $session = $this->sessionRepository->findActiveByUserId($userId);

        if ($session === null) {
            return ['success' => false, 'item' => null];
        }

        // 締切割り込みが提示されていない場合は失敗
        if (! $session->hasOfferedInterrupt()) {
            return ['success' => false, 'item' => null];
        }

        // 通常レーンからItemを選定
        $item = $this->selectionService->selectFromNormalLane($userId, $session);

        $now = new \DateTimeImmutable;

        if ($item !== null) {
            // Item の last_presented_at を更新
            $this->itemRepository->updateLastPresentedAt($item->id, $now);

            // セッションの current_item を更新
            $this->sessionRepository->updateCurrentItem($session->id, $item->id, $now);
        }

        return ['success' => true, 'item' => $item];
    }
}

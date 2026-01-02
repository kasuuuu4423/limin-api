<?php

declare(strict_types=1);

namespace App\UseCases;

use Domain\Limin\Entity\Item;
use Domain\Limin\Repository\ItemRepositoryInterface;
use Domain\Limin\ValueObject\NextAction;

final readonly class UpdateNextActionUseCase
{
    public function __construct(
        private ItemRepositoryInterface $itemRepository,
    ) {}

    /**
     * Item の nextAction を更新する
     *
     * @return bool 更新に成功したか（対象が存在したか）
     */
    public function execute(int $userId, string $itemId, string $nextAction): bool
    {
        $item = $this->itemRepository->findById($itemId);

        if ($item === null) {
            return false;
        }

        // 所有者チェック
        if ($item->userId !== $userId) {
            return false;
        }

        $now = new \DateTimeImmutable;

        $updatedItem = new Item(
            id: $item->id,
            userId: $item->userId,
            type: $item->type,
            state: $item->state,
            availability: $item->availability,
            nextAction: NextAction::create($nextAction),
            dueAt: $item->dueAt,
            timebox: $item->timebox,
            meta: $item->meta,
            lastPresentedAt: $item->lastPresentedAt,
            doneAt: $item->doneAt,
            createdAt: $item->createdAt,
            updatedAt: $now,
            deletedAt: $item->deletedAt,
        );

        $this->itemRepository->save($updatedItem);

        return true;
    }
}

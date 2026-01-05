<?php

declare(strict_types=1);

namespace App\UseCases;

use Domain\Limin\Entity\Item;
use Domain\Limin\Repository\ItemRepositoryInterface;

final readonly class CompleteItemUseCase
{
    public function __construct(
        private ItemRepositoryInterface $itemRepository,
    ) {}

    /**
     * Itemを完了状態にする
     *
     * @return bool 成功した場合はtrue、Itemが見つからない場合はfalse
     */
    public function execute(int $userId, string $itemId): bool
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
            title: $item->title,
            nextAction: $item->nextAction,
            dueAt: $item->dueAt,
            timebox: $item->timebox,
            meta: $item->meta,
            lastPresentedAt: $item->lastPresentedAt,
            doneAt: $now,
            createdAt: $item->createdAt,
            updatedAt: $now,
            deletedAt: $item->deletedAt,
        );

        $this->itemRepository->save($updatedItem);

        return true;
    }
}

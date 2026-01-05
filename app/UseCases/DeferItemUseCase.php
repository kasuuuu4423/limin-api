<?php

declare(strict_types=1);

namespace App\UseCases;

use Domain\Limin\Entity\Item;
use Domain\Limin\Repository\ItemRepositoryInterface;
use Domain\Limin\ValueObject\Availability;

final readonly class DeferItemUseCase
{
    public function __construct(
        private ItemRepositoryInterface $itemRepository,
    ) {}

    /**
     * Itemを先送り状態（LATER）にする
     *
     * 「今は無理」= このItemについて今は気力がない
     * availabilityをLATERに更新し、セッション内抑制のためlast_presented_atを記録
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
            availability: Availability::LATER,
            title: $item->title,
            nextAction: $item->nextAction,
            dueAt: $item->dueAt,
            timebox: $item->timebox,
            meta: $item->meta,
            lastPresentedAt: $now,
            doneAt: $item->doneAt,
            createdAt: $item->createdAt,
            updatedAt: $now,
            deletedAt: $item->deletedAt,
        );

        $this->itemRepository->save($updatedItem);

        return true;
    }
}

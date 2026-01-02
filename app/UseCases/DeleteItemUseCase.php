<?php

declare(strict_types=1);

namespace App\UseCases;

use Domain\Limin\Repository\ItemRepositoryInterface;

final readonly class DeleteItemUseCase
{
    public function __construct(
        private ItemRepositoryInterface $itemRepository,
    ) {}

    /**
     * Item を論理削除する
     *
     * @return bool 削除に成功したか（対象が存在したか）
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

        $this->itemRepository->delete($itemId);

        return true;
    }
}

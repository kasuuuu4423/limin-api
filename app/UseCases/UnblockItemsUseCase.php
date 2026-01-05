<?php

declare(strict_types=1);

namespace App\UseCases;

use Domain\Limin\Repository\ItemRepositoryInterface;

final readonly class UnblockItemsUseCase
{
    public function __construct(
        private ItemRepositoryInterface $itemRepository,
    ) {}

    /**
     * BLOCKEDのItemをすべてNOWに変更
     *
     * @return int 更新されたItem数
     */
    public function execute(int $userId): int
    {
        return $this->itemRepository->unblockAll($userId);
    }
}

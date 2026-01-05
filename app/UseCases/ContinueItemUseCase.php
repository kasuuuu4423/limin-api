<?php

declare(strict_types=1);

namespace App\UseCases;

use Domain\Limin\Entity\Item;
use Domain\Limin\Repository\ItemRepositoryInterface;
use Domain\Limin\ValueObject\NextAction;

/**
 * 次の一手を設定してタスクを継続する
 *
 * 完了時のフロー:
 * 1. クライアントが「完了」を押す
 * 2. 「次の一手がある？」を表示
 * 3. 「ある」を選択 → 次の一手を入力
 * 4. このUseCaseを呼び出す
 */
final readonly class ContinueItemUseCase
{
    public function __construct(
        private ItemRepositoryInterface $itemRepository,
    ) {}

    /**
     * Item の nextAction を更新してタスクを継続する
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
            title: $item->title,
            nextAction: NextAction::create($nextAction),
            dueAt: $item->dueAt,
            timebox: $item->timebox,
            meta: $item->meta,
            lastPresentedAt: null, // 継続するので再度選定対象にする
            doneAt: null, // 継続するので完了日時はクリア
            createdAt: $item->createdAt,
            updatedAt: $now,
            deletedAt: $item->deletedAt,
        );

        $this->itemRepository->save($updatedItem);

        return true;
    }
}

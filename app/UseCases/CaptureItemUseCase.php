<?php

declare(strict_types=1);

namespace App\UseCases;

use Domain\Limin\Entity\Item;
use Domain\Limin\Repository\ItemRepositoryInterface;
use Domain\Limin\ValueObject\Availability;
use Domain\Limin\ValueObject\ItemState;
use Domain\Limin\ValueObject\ItemType;
use Domain\Limin\ValueObject\NextAction;
use Illuminate\Support\Str;

final readonly class CaptureItemUseCase
{
    public function __construct(
        private ItemRepositoryInterface $itemRepository,
    ) {}

    /**
     * 1行登録を実行する
     *
     * @return string 作成されたItemのID
     */
    public function execute(int $userId, string $text): string
    {
        $now = new \DateTimeImmutable;
        $id = (string) Str::uuid7();

        $item = new Item(
            id: $id,
            userId: $userId,
            type: ItemType::TASK,
            state: ItemState::DO,
            availability: Availability::NOW,
            nextAction: NextAction::create($text),
            dueAt: null,
            timebox: null,
            meta: false,
            lastPresentedAt: null,
            doneAt: null,
            createdAt: $now,
            updatedAt: $now,
            deletedAt: null,
        );

        $this->itemRepository->save($item);

        return $id;
    }
}

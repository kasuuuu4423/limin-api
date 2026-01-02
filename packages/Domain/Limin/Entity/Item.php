<?php

declare(strict_types=1);

namespace Domain\Limin\Entity;

use Domain\Limin\ValueObject\Availability;
use Domain\Limin\ValueObject\ItemState;
use Domain\Limin\ValueObject\ItemType;
use Domain\Limin\ValueObject\NextAction;

final class Item
{
    public function __construct(
        public readonly string $id,
        public readonly int $userId,
        public readonly ItemType $type,
        public readonly ItemState $state,
        public readonly Availability $availability,
        public readonly NextAction $nextAction,
        public readonly ?\DateTimeImmutable $dueAt,
        public readonly ?int $timebox,
        public readonly bool $meta,
        public readonly ?\DateTimeImmutable $lastPresentedAt,
        public readonly ?\DateTimeImmutable $doneAt,
        public readonly \DateTimeImmutable $createdAt,
        public readonly \DateTimeImmutable $updatedAt,
        public readonly ?\DateTimeImmutable $deletedAt = null,
    ) {}

    public function isCompleted(): bool
    {
        return $this->doneAt !== null;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function isAvailableNow(): bool
    {
        return $this->availability === Availability::NOW;
    }

    public function isActionable(): bool
    {
        return $this->state === ItemState::DO;
    }

    public function hasDeadlineWithin(int $hours): bool
    {
        if ($this->dueAt === null) {
            return false;
        }

        $threshold = new \DateTimeImmutable("+{$hours} hours");

        return $this->dueAt <= $threshold;
    }
}

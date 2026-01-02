<?php

declare(strict_types=1);

namespace Domain\Limin\Entity;

final class Session
{
    public function __construct(
        public readonly string $id,
        public readonly int $userId,
        public readonly ?string $deviceId,
        public readonly \DateTimeImmutable $startedAt,
        public readonly ?\DateTimeImmutable $stoppedAt,
        public readonly ?string $currentItemId,
        public readonly ?\DateTimeImmutable $currentItemPresentedAt,
        public readonly ?\DateTimeImmutable $interruptOfferedAt,
        public readonly ?\DateTimeImmutable $interruptAcceptedAt,
        public readonly \DateTimeImmutable $createdAt,
        public readonly \DateTimeImmutable $updatedAt,
    ) {}

    public function isActive(): bool
    {
        return $this->stoppedAt === null;
    }

    public function hasOfferedInterrupt(): bool
    {
        return $this->interruptOfferedAt !== null;
    }

    public function hasAcceptedInterrupt(): bool
    {
        return $this->interruptAcceptedAt !== null;
    }
}

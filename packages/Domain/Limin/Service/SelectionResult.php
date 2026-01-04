<?php

declare(strict_types=1);

namespace Domain\Limin\Service;

use Domain\Limin\Entity\Item;

/**
 * 選定結果を表すDTO
 */
final readonly class SelectionResult
{
    private function __construct(
        public ?Item $item,
        public bool $isInterrupt,
        public bool $isEmpty,
    ) {}

    public static function normal(Item $item): self
    {
        return new self(
            item: $item,
            isInterrupt: false,
            isEmpty: false,
        );
    }

    public static function interrupt(Item $item): self
    {
        return new self(
            item: $item,
            isInterrupt: true,
            isEmpty: false,
        );
    }

    public static function empty(): self
    {
        return new self(
            item: null,
            isInterrupt: false,
            isEmpty: true,
        );
    }
}

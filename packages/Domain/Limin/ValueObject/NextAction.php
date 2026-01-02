<?php

declare(strict_types=1);

namespace Domain\Limin\ValueObject;

final class NextAction
{
    private const int MAX_LENGTH = 500;

    private function __construct(
        public readonly string $value,
    ) {}

    public static function create(string $value): self
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new \InvalidArgumentException('NextAction cannot be empty');
        }

        if (mb_strlen($trimmed) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('NextAction cannot exceed %d characters', self::MAX_LENGTH)
            );
        }

        return new self($trimmed);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

<?php

declare(strict_types=1);

namespace Domain\Limin\Repository;

use Domain\Limin\Entity\Item;

interface ItemRepositoryInterface
{
    public function findById(string $id): ?Item;

    public function save(Item $item): void;

    public function delete(string $id): void;
}

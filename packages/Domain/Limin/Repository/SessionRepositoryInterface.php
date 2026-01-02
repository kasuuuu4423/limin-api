<?php

declare(strict_types=1);

namespace Domain\Limin\Repository;

use Domain\Limin\Entity\Session;

interface SessionRepositoryInterface
{
    public function findById(string $id): ?Session;

    public function findActiveByUserId(int $userId): ?Session;

    public function save(Session $session): void;
}

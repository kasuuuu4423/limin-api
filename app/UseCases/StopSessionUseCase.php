<?php

declare(strict_types=1);

namespace App\UseCases;

use Domain\Limin\Repository\SessionRepositoryInterface;

final readonly class StopSessionUseCase
{
    public function __construct(
        private SessionRepositoryInterface $sessionRepository,
    ) {}

    /**
     * 現在のセッションを終了する
     *
     * @return bool セッションが存在して終了できた場合はtrue
     */
    public function execute(int $userId): bool
    {
        $session = $this->sessionRepository->findActiveByUserId($userId);

        if ($session === null) {
            return false;
        }

        $now = new \DateTimeImmutable;
        $this->sessionRepository->stop($session->id, $now);

        return true;
    }
}

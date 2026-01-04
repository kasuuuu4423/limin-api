<?php

declare(strict_types=1);

namespace Domain\Limin\Repository;

use Domain\Limin\Entity\Session;

interface SessionRepositoryInterface
{
    public function findById(string $id): ?Session;

    public function findActiveByUserId(int $userId): ?Session;

    public function save(Session $session): void;

    /**
     * 指定日時より前に開始されたアクティブセッションを取得
     *
     * @return array<Session>
     */
    public function findActiveSessionsStartedBefore(\DateTimeImmutable $before): array;

    /**
     * セッションを終了（stopped_atを設定）
     */
    public function stop(string $sessionId, \DateTimeImmutable $stoppedAt): void;

    /**
     * セッションの現在のItem情報を更新
     */
    public function updateCurrentItem(
        string $sessionId,
        string $itemId,
        \DateTimeImmutable $presentedAt
    ): void;

    /**
     * 締切割り込みを提示した日時を記録
     */
    public function markInterruptOffered(string $sessionId, \DateTimeImmutable $offeredAt): void;

    /**
     * 締切割り込みを採用した日時を記録
     */
    public function markInterruptAccepted(string $sessionId, \DateTimeImmutable $acceptedAt): void;
}

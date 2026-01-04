<?php

declare(strict_types=1);

namespace App\UseCases;

use Domain\Limin\Entity\Session;
use Domain\Limin\Repository\SessionRepositoryInterface;
use Illuminate\Support\Str;

final readonly class StartSessionUseCase
{
    public function __construct(
        private SessionRepositoryInterface $sessionRepository,
    ) {}

    /**
     * セッション開始を実行する
     * 既存のアクティブセッションがある場合は自動終了して新規開始
     *
     * @return string 作成されたセッションのID
     */
    public function execute(int $userId, ?string $deviceId = null): string
    {
        $now = new \DateTimeImmutable;

        // 既存のアクティブセッションを終了
        $existingSession = $this->sessionRepository->findActiveByUserId($userId);
        if ($existingSession !== null) {
            $this->sessionRepository->stop($existingSession->id, $now);
        }

        // 新規セッション作成
        $id = (string) Str::uuid7();
        $session = new Session(
            id: $id,
            userId: $userId,
            deviceId: $deviceId,
            startedAt: $now,
            stoppedAt: null,
            currentItemId: null,
            currentItemPresentedAt: null,
            interruptOfferedAt: null,
            interruptAcceptedAt: null,
            createdAt: $now,
            updatedAt: $now,
        );

        $this->sessionRepository->save($session);

        return $id;
    }
}

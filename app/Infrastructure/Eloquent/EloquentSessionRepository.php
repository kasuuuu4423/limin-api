<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquent;

use App\Infrastructure\Models\LiminSession;
use Domain\Limin\Entity\Session;
use Domain\Limin\Repository\SessionRepositoryInterface;

final class EloquentSessionRepository implements SessionRepositoryInterface
{
    public function findById(string $id): ?Session
    {
        $model = LiminSession::find($id);

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function findActiveByUserId(int $userId): ?Session
    {
        $model = LiminSession::where('user_id', $userId)
            ->whereNull('stopped_at')
            ->latest('started_at')
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function save(Session $session): void
    {
        LiminSession::updateOrCreate(
            ['id' => $session->id],
            [
                'user_id' => $session->userId,
                'device_id' => $session->deviceId,
                'started_at' => $session->startedAt,
                'stopped_at' => $session->stoppedAt,
                'current_item_id' => $session->currentItemId,
                'current_item_presented_at' => $session->currentItemPresentedAt,
                'interrupt_offered_at' => $session->interruptOfferedAt,
                'interrupt_accepted_at' => $session->interruptAcceptedAt,
            ]
        );
    }

    private function toEntity(LiminSession $model): Session
    {
        return new Session(
            id: $model->id,
            userId: $model->user_id,
            deviceId: $model->device_id,
            startedAt: $model->started_at->toDateTimeImmutable(),
            stoppedAt: $model->stopped_at?->toDateTimeImmutable(),
            currentItemId: $model->current_item_id,
            currentItemPresentedAt: $model->current_item_presented_at?->toDateTimeImmutable(),
            interruptOfferedAt: $model->interrupt_offered_at?->toDateTimeImmutable(),
            interruptAcceptedAt: $model->interrupt_accepted_at?->toDateTimeImmutable(),
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable(),
        );
    }
}

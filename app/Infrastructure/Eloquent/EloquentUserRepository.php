<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquent;

use App\Models\User as UserModel;
use Domain\Limin\Entity\User;
use Domain\Limin\Repository\UserRepositoryInterface;

final class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?User
    {
        $model = UserModel::find($id);

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function findByEmail(string $email): ?User
    {
        $model = UserModel::where('email', $email)->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function save(User $user): void
    {
        // Domain Entity からの保存は現時点では未実装
        // 認証時はEloquentモデルを直接使用するため
    }

    private function toEntity(UserModel $model): User
    {
        return new User(
            id: $model->id,
            name: $model->name,
            email: $model->email,
            laterRestoreAt: $model->later_restore_at,
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable(),
        );
    }
}

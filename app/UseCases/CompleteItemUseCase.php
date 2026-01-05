<?php

declare(strict_types=1);

namespace App\UseCases;

use App\Infrastructure\Models\Item as ItemModel;

final class CompleteItemUseCase
{
    /**
     * Itemを完了状態にする
     *
     * @return bool 成功した場合はtrue、Itemが見つからない場合はfalse
     */
    public function execute(int $userId, string $itemId): bool
    {
        $item = ItemModel::where('id', $itemId)
            ->where('user_id', $userId)
            ->first();

        if ($item === null) {
            return false;
        }

        $item->done_at = now();
        $item->save();

        return true;
    }
}





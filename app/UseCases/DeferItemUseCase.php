<?php

declare(strict_types=1);

namespace App\UseCases;

use App\Infrastructure\Models\Item as ItemModel;
use Domain\Limin\ValueObject\Availability;

final class DeferItemUseCase
{
    /**
     * Itemを先送り状態（LATER）にする
     *
     * 「今は無理」= このItemについて今は気力がない
     * availabilityをLATERに更新し、セッション内抑制のためlast_presented_atを記録
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

        $item->availability = Availability::LATER->value;
        $item->last_presented_at = now();
        $item->save();

        return true;
    }
}

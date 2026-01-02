<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquent;

use App\Infrastructure\Models\Item as ItemModel;
use Domain\Limin\Entity\Item;
use Domain\Limin\Repository\ItemRepositoryInterface;
use Domain\Limin\ValueObject\Availability;
use Domain\Limin\ValueObject\ItemState;
use Domain\Limin\ValueObject\ItemType;
use Domain\Limin\ValueObject\NextAction;

final class EloquentItemRepository implements ItemRepositoryInterface
{
    public function findById(string $id): ?Item
    {
        $model = ItemModel::find($id);

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function save(Item $item): void
    {
        $model = ItemModel::find($item->id);

        if ($model === null) {
            $model = new ItemModel;
            $model->id = $item->id;
        }

        $model->user_id = $item->userId;
        $model->type = $item->type->value;
        $model->state = $item->state->value;
        $model->availability = $item->availability->value;
        $model->next_action = $item->nextAction->value;
        $model->due_at = $item->dueAt;
        $model->timebox = $item->timebox;
        $model->meta = $item->meta;
        $model->last_presented_at = $item->lastPresentedAt;
        $model->done_at = $item->doneAt;

        $model->save();
    }

    public function delete(string $id): void
    {
        ItemModel::where('id', $id)->delete();
    }

    private function toEntity(ItemModel $model): Item
    {
        return new Item(
            id: $model->id,
            userId: $model->user_id,
            type: ItemType::from($model->type),
            state: ItemState::from($model->state),
            availability: Availability::from($model->availability),
            nextAction: NextAction::create($model->next_action),
            dueAt: $model->due_at?->toDateTimeImmutable(),
            timebox: $model->timebox,
            meta: $model->meta,
            lastPresentedAt: $model->last_presented_at?->toDateTimeImmutable(),
            doneAt: $model->done_at?->toDateTimeImmutable(),
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable(),
            deletedAt: $model->deleted_at?->toDateTimeImmutable(),
        );
    }
}

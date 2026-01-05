<?php

declare(strict_types=1);

namespace App\Infrastructure\Eloquent;

use App\Infrastructure\Models\Item as ItemModel;
use Carbon\Carbon;
use Domain\Limin\Entity\Item;
use Domain\Limin\Entity\Session;
use Domain\Limin\Repository\ItemRepositoryInterface;
use Domain\Limin\ValueObject\Availability;
use Domain\Limin\ValueObject\ItemState;
use Domain\Limin\ValueObject\ItemType;
use Domain\Limin\ValueObject\NextAction;
use Domain\Limin\ValueObject\Title;
use Illuminate\Database\Eloquent\Builder;

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
        $model->title = $item->title->value;
        $model->next_action = $item->nextAction?->value;
        $model->due_at = $item->dueAt !== null ? Carbon::instance($item->dueAt) : null;
        $model->timebox = $item->timebox;
        $model->meta = $item->meta;
        $model->last_presented_at = $item->lastPresentedAt !== null ? Carbon::instance($item->lastPresentedAt) : null;
        $model->done_at = $item->doneAt !== null ? Carbon::instance($item->doneAt) : null;

        $model->save();
    }

    public function delete(string $id): void
    {
        ItemModel::where('id', $id)->delete();
    }

    public function findNextCandidate(int $userId, Session $session): ?Item
    {
        // ランダム選定
        $model = $this->baseCandidateQuery($userId, $session)
            ->inRandomOrder()
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function findDeadlineCandidate(int $userId, Session $session, int $withinHours): ?Item
    {
        $threshold = new \DateTimeImmutable("+{$withinHours} hours");

        // 締切が近いItemを割り込み選定（締切順）
        $model = $this->baseCandidateQuery($userId, $session)
            ->whereNotNull('due_at')
            ->where('due_at', '<=', $threshold)
            ->orderBy('due_at', 'asc')
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function updateLastPresentedAt(string $itemId, \DateTimeImmutable $presentedAt): void
    {
        ItemModel::where('id', $itemId)
            ->update(['last_presented_at' => $presentedAt]);
    }

    /**
     * 通常レーン・締切割り込み共通の選定条件クエリ
     *
     * @return Builder<ItemModel>
     */
    private function baseCandidateQuery(int $userId, Session $session): Builder
    {
        return ItemModel::query()
            ->where('user_id', $userId)
            ->where('availability', Availability::NOW->value)
            ->where('state', ItemState::DO->value)
            ->whereNull('done_at')
            ->where(function (Builder $query) use ($session) {
                // セッション開始後に先送りされたItemを除外
                // 条件: NOT (last_presented_at >= session.started_at AND availability != NOW)
                // availabilityがNOWの条件は上で既に指定しているので、
                // ここでは「セッション開始後に提示されて、今はNOWでない」ケースを除外
                // しかし、availability = NOW は上で保証されているため、
                // 実際には last_presented_at がセッション開始前 OR null のものを含める
                $query->whereNull('last_presented_at')
                    ->orWhere('last_presented_at', '<', $session->startedAt);
            });
    }

    private function toEntity(ItemModel $model): Item
    {
        return new Item(
            id: $model->id,
            userId: $model->user_id,
            type: ItemType::from($model->type),
            state: ItemState::from($model->state),
            availability: Availability::from($model->availability),
            title: Title::create($model->title),
            nextAction: $model->next_action !== null && $model->next_action !== ''
                ? NextAction::create($model->next_action)
                : null,
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

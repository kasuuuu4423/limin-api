<?php

declare(strict_types=1);

namespace Domain\Limin\Repository;

use Domain\Limin\Entity\Item;
use Domain\Limin\Entity\Session;

interface ItemRepositoryInterface
{
    public function findById(string $id): ?Item;

    public function save(Item $item): void;

    public function delete(string $id): void;

    /**
     * 通常レーン用の次のItem候補を取得
     *
     * 選定条件:
     * - availability = NOW
     * - state = DO
     * - done_at = NULL（未完了）
     * - セッション開始後に先送りされたItemを除外
     *
     * ※ next_action の有無は選定条件に含めない
     *
     * @return Item|null 選定されたItem（なければnull）
     */
    public function findNextCandidate(int $userId, Session $session): ?Item;

    /**
     * 締切割り込み用のItem候補を取得
     *
     * 選定条件:
     * - 通常レーン条件を満たす
     * - due_at が存在
     * - due_at が指定時間以内
     *
     * @return Item|null 締切が最も近いItem（なければnull）
     */
    public function findDeadlineCandidate(int $userId, Session $session, int $withinHours): ?Item;

    /**
     * Itemの last_presented_at を更新
     */
    public function updateLastPresentedAt(string $itemId, \DateTimeImmutable $presentedAt): void;

    /**
     * BLOCKEDのItemをすべてNOWに変更
     *
     * @return int 更新されたItem数
     */
    public function unblockAll(int $userId): int;
}

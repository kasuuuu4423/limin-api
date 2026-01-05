<?php

declare(strict_types=1);

namespace Domain\Limin\Service;

use Domain\Limin\Entity\Item;
use Domain\Limin\Entity\Session;
use Domain\Limin\Repository\ItemRepositoryInterface;

/**
 * 「次の1件」選定ロジック
 *
 * 選定レーン:
 * 1. 締切割り込みレーン: dueAtが48時間以内、セッション中1回のみ
 * 2. 通常レーン: availability=NOW, state=DO, done_at IS NULL, ランダム選定
 *
 * ※ next_action の有無は選定条件に含めない
 */
final readonly class ItemSelectionService
{
    private const DEADLINE_THRESHOLD_HOURS = 48;

    public function __construct(
        private ItemRepositoryInterface $itemRepository,
    ) {}

    /**
     * 次のItemを選定する
     *
     * @return SelectionResult 選定結果（通常/締切割り込み/なし）
     */
    public function selectNext(int $userId, Session $session): SelectionResult
    {
        // 1. 締切割り込みの判定
        if (! $session->hasOfferedInterrupt()) {
            $deadlineItem = $this->itemRepository->findDeadlineCandidate(
                $userId,
                $session,
                self::DEADLINE_THRESHOLD_HOURS
            );

            if ($deadlineItem !== null) {
                return SelectionResult::interrupt($deadlineItem);
            }
        }

        // 2. 通常レーン選定
        $normalItem = $this->itemRepository->findNextCandidate($userId, $session);

        if ($normalItem !== null) {
            return SelectionResult::normal($normalItem);
        }

        return SelectionResult::empty();
    }

    /**
     * 通常レーンからItemを選定する（締切割り込み却下後に使用）
     */
    public function selectFromNormalLane(int $userId, Session $session): ?Item
    {
        return $this->itemRepository->findNextCandidate($userId, $session);
    }
}

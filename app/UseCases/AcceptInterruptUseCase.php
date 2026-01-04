<?php

declare(strict_types=1);

namespace App\UseCases;

use Domain\Limin\Repository\SessionRepositoryInterface;

final readonly class AcceptInterruptUseCase
{
    public function __construct(
        private SessionRepositoryInterface $sessionRepository,
    ) {}

    /**
     * 締切割り込みを採用する
     *
     * @return bool 成功したかどうか（falseの場合は割り込みが提示されていない）
     */
    public function execute(int $userId): bool
    {
        $session = $this->sessionRepository->findActiveByUserId($userId);

        if ($session === null) {
            return false;
        }

        // 締切割り込みが提示されていない場合は失敗
        if (! $session->hasOfferedInterrupt()) {
            return false;
        }

        // 既に採用済みの場合は成功とみなす
        if ($session->hasAcceptedInterrupt()) {
            return true;
        }

        $now = new \DateTimeImmutable;

        // interrupt_accepted_at を記録
        $this->sessionRepository->markInterruptAccepted($session->id, $now);

        // current_item を更新（interrupt_offered_at 時点で提示されたItemが current_item になる）
        // 注: current_item_id は /next で締切割り込みを返した時点では未設定なので、
        //     クライアント側で item.id を保持してもらう必要がある
        //     または、session に interrupt_item_id を持たせる設計も考えられる
        //     現状の設計では、/next の戻り値の item.id をクライアントが保持している前提
        if ($session->currentItemId !== null) {
            $this->sessionRepository->updateCurrentItem($session->id, $session->currentItemId, $now);
        }

        return true;
    }
}

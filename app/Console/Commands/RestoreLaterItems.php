<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Infrastructure\Models\Item as ItemModel;
use App\Models\User;
use Domain\Limin\ValueObject\Availability;
use Illuminate\Console\Command;

final class RestoreLaterItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:restore-later';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore LATER items to NOW based on user settings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now();
        $currentTime = $now->format('H:i:s');

        // later_restore_at が現在時刻と一致するユーザーを取得
        // 1分の幅を持たせて判定（スケジューラーが毎分実行される想定）
        $users = User::query()
            ->whereRaw('later_restore_at BETWEEN ? AND ?', [
                $now->copy()->startOfMinute()->format('H:i:s'),
                $now->copy()->endOfMinute()->format('H:i:s'),
            ])
            ->get();

        $totalRestored = 0;

        foreach ($users as $user) {
            $restored = ItemModel::where('user_id', $user->id)
                ->where('availability', Availability::LATER->value)
                ->whereNull('done_at')
                ->whereNull('deleted_at')
                ->update(['availability' => Availability::NOW->value]);

            $totalRestored += $restored;

            if ($restored > 0) {
                $this->info("User {$user->id}: Restored {$restored} items from LATER to NOW");
            }
        }

        $this->info("Total restored: {$totalRestored} items");

        return Command::SUCCESS;
    }
}

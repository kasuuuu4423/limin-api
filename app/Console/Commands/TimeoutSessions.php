<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Domain\Limin\Repository\SessionRepositoryInterface;
use Illuminate\Console\Command;

final class TimeoutSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'session:timeout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Terminate sessions that have been active for more than 24 hours';

    public function __construct(
        private readonly SessionRepositoryInterface $sessionRepository,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $threshold = new \DateTimeImmutable('-24 hours');
        $now = new \DateTimeImmutable;

        $sessions = $this->sessionRepository->findActiveSessionsStartedBefore($threshold);

        $count = count($sessions);

        foreach ($sessions as $session) {
            $this->sessionRepository->stop($session->id, $now);
        }

        if ($count > 0) {
            $this->info("Terminated {$count} timed-out session(s).");
        } else {
            $this->info('No timed-out sessions found.');
        }

        return Command::SUCCESS;
    }
}

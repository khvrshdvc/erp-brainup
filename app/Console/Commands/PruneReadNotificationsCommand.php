<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneReadNotificationsCommand extends Command
{
    protected $signature = 'notifications:prune {--days=60}';

    protected $description = 'O‘qilgan eski bildirishnomalarni o‘chiradi';

    public function handle(): int
    {
        $cutoff = now()->subDays(max(1, (int) $this->option('days')));
        $deleted = 0;

        // Delete in batches so a large backlog never holds a long lock on the table.
        do {
            $batch = DB::table('notifications')
                ->whereNotNull('read_at')
                ->where('read_at', '<', $cutoff)
                ->limit(5000)
                ->delete();

            $deleted += $batch;
        } while ($batch > 0);

        $this->info("O‘chirildi: {$deleted} ta bildirishnoma");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Enums\SubmissionStatus;
use App\Models\Attachment;
use App\Models\Setting;
use App\Models\Submission;
use Illuminate\Console\Command;

class PruneSubmissionFilesCommand extends Command
{
    protected $signature = 'submissions:prune-files';

    protected $description = 'Tekshirilgan topshiriqlarning saqlash muddati o‘tgan fayllarini o‘chiradi (ball va fikr saqlanadi)';

    public function handle(): int
    {
        $days = Setting::submissionFileRetentionDays();

        if ($days === 0) {
            $this->info('Fayllarni saqlash muddati cheklanmagan (0), hech narsa o‘chirilmadi.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);
        $files = 0;
        $bytes = 0;

        $expired = Submission::withoutGlobalScopes()
            ->select('id')
            ->where('status', SubmissionStatus::Graded->value)
            ->whereNotNull('graded_at')
            ->where('graded_at', '<', $cutoff);

        // Deleting through the model removes the stored file too (Attachment's deleting event).
        Attachment::query()
            ->where('attachable_type', (new Submission)->getMorphClass())
            ->whereIn('attachable_id', $expired)
            ->chunkById(200, function ($attachments) use (&$files, &$bytes) {
                foreach ($attachments as $attachment) {
                    $size = $attachment->size;

                    if ($attachment->delete()) {
                        $files++;
                        $bytes += $size;
                    }
                }
            });

        $megabytes = number_format($bytes / 1048576, 2, '.', '');

        $this->info("O‘chirildi: {$files} ta fayl, {$megabytes} MB bo‘shatildi");

        return self::SUCCESS;
    }
}

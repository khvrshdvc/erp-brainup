<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PruneNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function notification(User $user, ?string $readAt): string
    {
        $id = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $id,
            'type' => 'test',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => '{}',
            'read_at' => $readAt,
            'created_at' => now()->subDays(100),
            'updated_at' => now()->subDays(100),
        ]);

        return $id;
    }

    public function test_only_read_notifications_older_than_the_cutoff_are_deleted(): void
    {
        $user = User::factory()->create();

        $oldRead = $this->notification($user, now()->subDays(61)->toDateTimeString());
        $recentRead = $this->notification($user, now()->subDays(59)->toDateTimeString());
        $oldUnread = $this->notification($user, null);

        $this->artisan('notifications:prune')->assertSuccessful();

        $this->assertDatabaseMissing('notifications', ['id' => $oldRead]);
        $this->assertDatabaseHas('notifications', ['id' => $recentRead]);
        $this->assertDatabaseHas('notifications', ['id' => $oldUnread]);
    }

    public function test_pruning_is_scheduled_daily(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('notifications:prune --days=60')
            ->assertSuccessful();
    }
}

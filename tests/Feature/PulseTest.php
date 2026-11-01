<?php

namespace Tests\Feature;

use __PHP_Incomplete_Class;
use App\Enums\Role;
use App\Models\Branch;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Laravel\Pulse\Contracts\ResolvesUsers;
use Laravel\Pulse\Livewire\Cache as CacheCard;
use Laravel\Pulse\Livewire\Exceptions;
use Laravel\Pulse\Livewire\Queues;
use Laravel\Pulse\Livewire\Servers;
use Laravel\Pulse\Livewire\SlowJobs;
use Laravel\Pulse\Livewire\SlowOutgoingRequests;
use Laravel\Pulse\Livewire\SlowQueries;
use Laravel\Pulse\Livewire\SlowRequests;
use Laravel\Pulse\Livewire\Usage;
use Laravel\Pulse\Pulse;
use Livewire\Features\SupportAutoInjectedAssets\SupportAutoInjectedAssets;
use Livewire\Livewire;
use stdClass;
use Tests\TestCase;

class PulseTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // Livewire keeps this in a static; a real request starts a new process, tests share one.
        SupportAutoInjectedAssets::$hasRenderedAComponentThisRequest = false;

        parent::tearDown();
    }

    public function test_only_the_configured_email_can_open_the_dashboard(): void
    {
        $this->get('/pulse')->assertForbidden();

        $branch = Branch::create(['name' => 'Test filial']);

        foreach ([Role::SuperAdmin, Role::BranchAdmin, Role::Student] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role, 'branch_id' => $branch->id]))
                ->get('/pulse')->assertForbidden();
        }

        $teacher = User::factory()->create(['role' => Role::Teacher]);
        $teacher->branches()->attach($branch->id);
        $this->actingAs($teacher)->get('/pulse')->assertForbidden();

        $owner = User::factory()->create(['role' => Role::Teacher, 'email' => 'UTKIRBEK@brainup.uz']);
        $owner->branches()->attach($branch->id);
        $this->actingAs($owner)->get('/pulse')->assertOk();
    }

    public function test_values_like_the_ones_pulse_caches_survive_a_serializing_store(): void
    {
        config(['cache.stores.serializing' => ['driver' => 'array', 'serialize' => true]]);
        $latest = CarbonImmutable::now();

        Cache::store('serializing')->put('pulse', [collect([(object) ['latest' => $latest, 'cpu' => collect([1, 2])]]), 1.5, 'str'], 60);

        [$rows, $duration, $start] = Cache::store('serializing')->get('pulse');

        $this->assertInstanceOf(Collection::class, $rows);
        $this->assertSame(1, $rows->count());
        $this->assertInstanceOf(stdClass::class, $rows->first());
        $this->assertInstanceOf(CarbonImmutable::class, $rows->first()->latest);
        $this->assertSame($latest->toDateTimeString(), $rows->first()->latest->toDateTimeString());
        $this->assertSame($latest->timezoneName, $rows->first()->latest->timezoneName);
        $this->assertSame(3, $rows->first()->cpu->sum());
        $this->assertSame(1.5, $duration);
        $this->assertSame('str', $start);
    }

    public function test_other_classes_still_do_not_come_back_from_the_cache(): void
    {
        config(['cache.stores.serializing' => ['driver' => 'array', 'serialize' => true]]);

        Cache::store('serializing')->put('user', new User, 60);

        $this->assertInstanceOf(__PHP_Incomplete_Class::class, Cache::store('serializing')->get('user'));
    }

    public function test_dashboard_opens_twice_in_a_row_with_a_serializing_cache(): void
    {
        $this->useSerializingCache();

        $owner = User::factory()->create(['role' => Role::SuperAdmin, 'email' => 'utkirbek@brainup.uz']);

        $this->actingAs($owner)->get('/pulse')->assertOk();
        $this->actingAs($owner)->get('/pulse')->assertOk();
    }

    public function test_cards_show_their_data_when_it_comes_from_the_cache(): void
    {
        // The dashboard page only holds lazy placeholders; the cards load, and read the cache, in later requests.
        $this->useSerializingCache();
        $this->recordPulseData();

        $cards = [
            Servers::class => 'web-1',
            Usage::class => 'Pulse Egasi',
            Queues::class => 'redis:default',
            CacheCard::class => 'pulse-key',
            SlowQueries::class => 'sample_table',
            Exceptions::class => 'SampleError',
            SlowRequests::class => '/slow-page',
            SlowJobs::class => 'SampleJob',
            SlowOutgoingRequests::class => 'slow.example.test',
        ];

        foreach ($cards as $card => $text) {
            Livewire::withoutLazyLoading()->test($card)->assertOk()->assertSee($text);
            Livewire::withoutLazyLoading()->test($card)->assertOk()->assertSee($text);
        }

        Cache::store('file')->flush();
    }

    /**
     * The real stores unserialize with cache.serializable_classes, unlike the tests' default array store.
     */
    private function useSerializingCache(): void
    {
        config(['cache.default' => 'file', 'cache.stores.file.path' => storage_path('framework/testing/pulse-cache')]);
        Cache::store('file')->flush();
    }

    private function recordPulseData(): void
    {
        $pulse = app(Pulse::class)->startRecording();
        $owner = User::factory()->create(['role' => Role::Student, 'name' => 'Pulse Egasi']);

        $pulse->set('system', 'web-1', json_encode([
            'name' => 'web-1', 'cpu' => 10, 'memory_used' => 100, 'memory_total' => 200,
            'storage' => [['directory' => '/', 'total' => 100, 'used' => 10]],
        ]));
        $pulse->record('cpu', 'web-1', 10)->avg()->onlyBuckets();
        $pulse->record('memory', 'web-1', 100)->avg()->onlyBuckets();
        $pulse->record('user_request', (string) $owner->id)->count();
        $pulse->record('queued', 'redis:default')->count()->onlyBuckets();
        $pulse->record('cache_hit', 'pulse-key')->count();
        $pulse->record('slow_query', json_encode(['select * from sample_table', 'app/Sample.php:1']), 1500)->max()->count();
        $pulse->record('exception', json_encode(['SampleError', 'app/Sample.php:1']), now()->getTimestamp())->max()->count();
        $pulse->record('slow_request', json_encode(['GET', '/slow-page', 'Closure']), 1500)->max()->count();
        $pulse->record('slow_job', 'SampleJob', 1500)->max()->count();
        $pulse->record('slow_outgoing_request', json_encode(['GET', 'https://slow.example.test']), 1500)->max()->count();
        $pulse->ingest();
    }

    public function test_user_cards_do_not_send_emails_to_gravatar(): void
    {
        $student = User::factory()->create(['role' => Role::Student, 'name' => 'Ali Valiyev']);

        $card = app(ResolvesUsers::class)->load(collect([$student->id]))->find($student->id);

        $this->assertSame('Ali Valiyev', $card->name);
        $this->assertFalse(isset($card->avatar));
    }

    public function test_pages_of_the_app_do_not_load_livewire(): void
    {
        $this->get(route('login'))->assertOk()->assertDontSee('livewire', false);
    }
}

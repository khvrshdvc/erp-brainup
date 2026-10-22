<?php

namespace Tests\Feature;

use App\Enums\AssignmentStatus;
use App\Enums\AttendanceStatus;
use App\Enums\Role;
use App\Enums\ShopOrderStatus;
use App\Enums\SubmissionStatus;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\ShopItem;
use App\Models\ShopOrder;
use App\Models\StudyGroup;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\NewAssignment;
use App\Notifications\SubmissionReceived;
use Database\Seeders\GamificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Opens the pages of every role in English and Russian and fails when Uzbek text is still shown.
 * The fixtures use English names, so any Uzbek spelling on the page is an untranslated interface text.
 */
class LocalizedPagesTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $superAdmin;

    private User $branchAdmin;

    private User $teacher;

    private User $student;

    private StudyGroup $group;

    private Assignment $assignment;

    private Submission $submission;

    private ShopItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GamificationSeeder::class);

        $this->branch = Branch::create(['name' => 'Main branch']);

        $this->superAdmin = User::factory()->create(['role' => Role::SuperAdmin]);
        $this->branchAdmin = User::factory()->create(['role' => Role::BranchAdmin, 'branch_id' => $this->branch->id]);
        $this->teacher = User::factory()->create(['role' => Role::Teacher]);
        $this->teacher->branches()->attach($this->branch->id);
        $this->student = User::factory()->create(['role' => Role::Student, 'branch_id' => $this->branch->id, 'xp' => 600]);

        $this->group = StudyGroup::create([
            'branch_id' => $this->branch->id,
            'teacher_id' => $this->teacher->id,
            'name' => 'English-1',
            'capacity' => 10,
            'schedule' => ['days' => ['mon', 'wed'], 'time' => '15:30'],
            'starts_on' => now()->startOfMonth(),
        ]);
        $this->group->students()->attach($this->student->id);

        $this->assignment = Assignment::create([
            'branch_id' => $this->branch->id,
            'study_group_id' => $this->group->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Essay',
            'description' => 'Write an essay.',
            'max_score' => 100,
            'due_at' => now()->addDays(3),
            'status' => AssignmentStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        $this->submission = Submission::create([
            'branch_id' => $this->branch->id,
            'assignment_id' => $this->assignment->id,
            'student_id' => $this->student->id,
            'status' => SubmissionStatus::Submitted,
            'is_late' => false,
            'submitted_at' => now(),
            'body' => 'Done.',
        ]);

        Attendance::create([
            'branch_id' => $this->branch->id,
            'study_group_id' => $this->group->id,
            'student_id' => $this->student->id,
            'lesson_date' => today()->subDay(),
            'status' => AttendanceStatus::Present,
        ]);

        $this->item = ShopItem::factory()->create(['name' => 'Notebook', 'description' => 'A nice notebook', 'price' => 100]);
        ShopOrder::create([
            'user_id' => $this->student->id,
            'shop_item_id' => $this->item->id,
            'branch_id' => $this->branch->id,
            'price' => 100,
            'status' => ShopOrderStatus::Pending,
        ]);

        $this->student->notify(new NewAssignment($this->assignment));
        $this->teacher->notify(new SubmissionReceived($this->submission));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function languages(): array
    {
        return ['English' => ['en', 'Sign in'], 'Russian' => ['ru', 'Вход в систему']];
    }

    private function assertNoUzbek(string $html, string $where): void
    {
        $text = preg_replace(['#<script\b.*?</script>#s', '#<style\b.*?</style>#s', '#<!--.*?-->#s', '#<form[^>]*class="lang-menu.*?</form>#s'], '', $html);

        $patterns = [
            // Uzbek Latin spelling: o‘ g‘ and the apostrophe in e‘lon / ma‘lumot.
            '/.{0,40}[A-Za-z][‘ʻ][A-Za-z].{0,40}/u',
            '/.{0,40}\b(Guruh\w*|Vazifa\w*|Topshir\w*|Davomat\w*|Holat|Saqlash|Bekor|Filial\w*|Parol\w*|Telefon|Qidir\w*|Barchasi|Bugun|Tahrirlash|Chiqish|Kirish|Sozlamalar|Hisobotlar|Yutuq\w*|Nishon\w*|Buyurtma\w*|Mahsulot\w*|Tekshir\w*|Muddat\w*|Daraja|Kurs|Jadval|Sana|Izoh|Baho|Keldi|Kelmadi|Kechikdi|Sababli)\b.{0,40}/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $match)) {
                $this->fail("Uzbek text on {$where}: ".trim(preg_replace('/\s+/', ' ', $match[0])));
            }
        }

        $this->addToAssertionCount(1);
    }

    private function pagesFor(string $role): array
    {
        $g = $this->group->id;

        return match ($role) {
            'super' => [
                route('admin.dashboard'), route('admin.groups.index'), route('admin.groups.create'), route('admin.groups.show', $g),
                route('admin.groups.edit', $g), route('admin.students.index'), route('admin.students.create'),
                route('admin.students.show', $this->student), route('admin.students.edit', $this->student),
                route('admin.teachers.index'), route('admin.teachers.create'), route('admin.teachers.edit', $this->teacher),
                route('admin.assignments.index'), route('admin.assignments.show', $this->assignment),
                route('admin.branches.index'), route('admin.branches.create'), route('admin.branches.edit', $this->branch),
                route('admin.gamification.index'), route('admin.reports.index'), route('admin.settings.edit'),
                route('admin.shop.orders.index'), route('admin.shop-items.index'), route('admin.shop-items.create'),
                route('admin.shop-items.edit', $this->item), route('submissions.index'), route('attendance.create', $g),
                route('notifications.index'), route('profile.edit'), route('search', ['q' => 'English']),
            ],
            'branch' => [
                route('admin.dashboard'), route('admin.settings.edit'), route('admin.reports.index'), route('groups.index'),
                route('submissions.index'), route('profile.edit'),
            ],
            'teacher' => [
                route('teacher.dashboard'), route('groups.index'), route('teacher.assignments.index'),
                route('teacher.assignments.create'), route('teacher.assignments.show', $this->assignment),
                route('teacher.assignments.edit', $this->assignment), route('attendance.create', $g),
                route('submissions.index'), route('notifications.index'), route('profile.edit'),
            ],
            'student' => [
                route('student.dashboard'), route('student.assignments.index'), route('student.assignments.show', $this->assignment),
                route('student.assignments.submit', $this->assignment), route('student.submissions.index'),
                route('student.achievements'), route('student.groups.index'), route('student.shop.index'),
                route('student.shop.orders'), route('notifications.index'), route('profile.edit'),
            ],
        };
    }

    #[DataProvider('languages')]
    public function test_pages_are_fully_translated(string $locale, string $loginText): void
    {
        $users = ['super' => $this->superAdmin, 'branch' => $this->branchAdmin, 'teacher' => $this->teacher, 'student' => $this->student];

        foreach ($users as $role => $user) {
            $user->forceFill(['locale' => $locale])->save();

            foreach ($this->pagesFor($role) as $url) {
                $response = $this->actingAs($user->fresh())->get($url);

                $response->assertOk();
                $this->assertNoUzbek($response->getContent(), "{$url} ({$role}, {$locale})");
            }
        }

        auth()->logout();

        $this->withCookie('locale', $locale)->get(route('login'))->assertSee($loginText);
    }

    #[DataProvider('languages')]
    public function test_notifications_follow_the_readers_language(string $locale, string $unused): void
    {
        $this->student->forceFill(['locale' => $locale])->save();

        $page = $this->actingAs($this->student->fresh())->get(route('notifications.index'));

        $page->assertOk();
        preg_match_all('#<span class="notif-body">(.*?)</span>#s', $page->getContent(), $bodies);
        preg_match_all('#<b>(.*?)</b>#s', $page->getContent(), $titles);
        $body = implode(' | ', array_merge($titles[1], $bodies[1]));
        $this->assertStringNotContainsString('Yangi vazifa', $body);
        $this->assertStringContainsString($locale === 'en' ? 'New assignment' : 'Новое задание', $body);
        $this->assertStringContainsString($locale === 'en' ? 'due: ' : 'срок: ', $body);
    }
}

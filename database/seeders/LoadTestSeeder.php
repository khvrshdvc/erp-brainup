<?php

namespace Database\Seeders;

use App\Enums\AssignmentStatus;
use App\Enums\AttendanceStatus;
use App\Enums\GroupStatus;
use App\Enums\Role;
use App\Enums\SubmissionStatus;
use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Level;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Production-sized data to check performance locally (never called by DatabaseSeeder):
 * 3 branches, 12 teachers, 2000 students in ~140 groups and 3 months of attendance,
 * assignments and submissions. Everything uses the password "password"; logins are
 * branchadmin1@loadtest.uz … branchadmin3@loadtest.uz, teacher01@loadtest.uz … and
 * student0001@loadtest.uz …
 *
 *   vendor/bin/sail artisan db:seed --class=LoadTestSeeder
 *
 * XP, levels and streaks are filled with random values; run `gamification:rebuild`
 * afterwards to compute the real ones from the attendance and submissions.
 * Rows are inserted in bulk, so no events, notifications or queued jobs are fired.
 * It does nothing if load-test users already exist.
 */
class LoadTestSeeder extends Seeder
{
    private const DOMAIN = 'loadtest.uz';

    private const CHUNK = 2000;

    public int $branches = 3;

    public int $teachers = 12;

    public int $students = 2000;

    public int $groups = 140;

    public int $days = 90;

    public int $assignmentsPerGroup = 12;

    private string $password;

    private string $now;

    public function configure(int $branches, int $teachers, int $students, int $groups, int $days, int $assignmentsPerGroup): static
    {
        $this->branches = $branches;
        $this->teachers = $teachers;
        $this->students = $students;
        $this->groups = $groups;
        $this->days = $days;
        $this->assignmentsPerGroup = $assignmentsPerGroup;

        return $this;
    }

    public function run(): void
    {
        if (DB::table('users')->where('email', 'like', '%@'.self::DOMAIN)->exists()) {
            $this->command?->warn('Yuklama sinovi ma‘lumotlari allaqachon mavjud, o‘tkazib yuborildi.');

            return;
        }

        mt_srand(20261022);

        $this->call([GamificationSeeder::class, CourseSeeder::class]);

        $this->password = Hash::make('password');
        $this->now = now()->toDateTimeString();

        DB::transaction(function () {
            $branchIds = $this->createBranches();
            $teachers = $this->createTeachers($branchIds);
            $groups = $this->createGroups($branchIds, $teachers);
            $members = $this->createStudents($branchIds, $groups);

            $this->createAttendance($groups, $members);
            $this->createSubmissions($groups, $members);
        });

        $this->command?->info(sprintf(
            'Tayyor: %d filial, %d o‘qituvchi, %d o‘quvchi, %d guruh, %d ta davomat, %d ta topshiriq.',
            $this->branches,
            $this->teachers,
            $this->students,
            $this->groups,
            DB::table('attendances')->count(),
            DB::table('submissions')->count(),
        ));
    }

    /**
     * @return list<int>
     */
    private function createBranches(): array
    {
        $ids = [];

        for ($i = 1; $i <= $this->branches; $i++) {
            $branch = Branch::firstOrCreate(['name' => "Sinov filiali {$i}"], ['address' => "Sinov manzili {$i}", 'is_active' => true]);

            $ids[] = $branch->id;

            DB::table('users')->insert($this->user("Filial admini {$i}", "branchadmin{$i}@".self::DOMAIN, Role::BranchAdmin, $this->phone(1, $i), $branch->id));
        }

        return $ids;
    }

    /**
     * @param  list<int>  $branchIds
     * @return array<int, list<int>> teacher ids by branch id
     */
    private function createTeachers(array $branchIds): array
    {
        $byBranch = [];

        for ($i = 1; $i <= $this->teachers; $i++) {
            $branchId = $branchIds[($i - 1) % count($branchIds)];

            $id = DB::table('users')->insertGetId($this->user(
                $this->name($i + 5000),
                sprintf('teacher%02d@%s', $i, self::DOMAIN),
                Role::Teacher,
                $this->phone(2, $i),
                $branchId,
            ));

            DB::table('branch_user')->insert(['branch_id' => $branchId, 'user_id' => $id]);

            $byBranch[$branchId][] = $id;
        }

        return $byBranch;
    }

    /**
     * @param  list<int>  $branchIds
     * @param  array<int, list<int>>  $teachers
     * @return list<array{id: int, branch_id: int, teacher_id: int, days: list<int>}>
     */
    private function createGroups(array $branchIds, array $teachers): array
    {
        $courseIds = Course::query()->pluck('id')->all();
        $startsOn = now()->subDays($this->days)->toDateString();
        $capacity = max(20, (int) ceil($this->students / max(1, $this->groups) * 1.5));
        $schedules = [
            ['mon', 'wed', 'fri'],
            ['tue', 'thu', 'sat'],
            ['mon', 'thu'],
        ];

        $groups = [];

        for ($i = 0; $i < $this->groups; $i++) {
            $branchId = $branchIds[$i % count($branchIds)];
            $teacherId = $teachers[$branchId][intdiv($i, count($branchIds)) % count($teachers[$branchId])];
            $schedule = $schedules[$i % count($schedules)];

            $id = DB::table('study_groups')->insertGetId([
                'branch_id' => $branchId,
                'course_id' => $courseIds[$i % count($courseIds)],
                'teacher_id' => $teacherId,
                'name' => sprintf('LT-%03d', $i + 1),
                'starts_on' => $startsOn,
                'ends_on' => now()->addMonths(3)->toDateString(),
                'schedule' => json_encode(['days' => $schedule, 'time' => ['09:00', '14:00', '18:00'][$i % 3]]),
                'capacity' => $capacity,
                'status' => GroupStatus::Active->value,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);

            $groups[] = [
                'id' => $id,
                'branch_id' => $branchId,
                'teacher_id' => $teacherId,
                'days' => array_map(fn (string $day) => ['mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6][$day], $schedule),
            ];
        }

        return $groups;
    }

    /**
     * @param  list<int>  $branchIds
     * @param  list<array{id: int, branch_id: int, teacher_id: int, days: list<int>}>  $groups
     * @return array<int, list<array{id: int, reliability: float}>> students by group id
     */
    private function createStudents(array $branchIds, array $groups): array
    {
        $levels = Level::query()->orderByDesc('min_xp')->get(['id', 'min_xp']);

        $groupsByBranch = collect($groups)->groupBy('branch_id');
        $members = [];
        $rows = [];
        $firstId = (int) DB::table('users')->max('id');

        for ($i = 1; $i <= $this->students; $i++) {
            $branchId = $branchIds[($i - 1) % count($branchIds)];
            $xp = mt_rand(0, 2500);

            $rows[] = $this->user($this->name($i), sprintf('student%04d@%s', $i, self::DOMAIN), Role::Student, $this->phone(0, $i), $branchId)
                + ['xp' => $xp, 'level_id' => $levels->first(fn ($level) => $level->min_xp <= $xp)?->id, 'streak' => mt_rand(0, 12)];

            if (count($rows) === self::CHUNK) {
                $this->flush('users', $rows);
            }
        }

        $this->flush('users', $rows);

        // Ids are consecutive within this insert run, so students can be mapped without re-reading them.
        $studentIds = DB::table('users')->where('id', '>', $firstId)->where('role', Role::Student->value)->orderBy('id')->pluck('id', 'email');

        $pivot = [];

        for ($i = 1; $i <= $this->students; $i++) {
            $studentId = $studentIds[sprintf('student%04d@%s', $i, self::DOMAIN)];
            $branchGroups = $groupsByBranch[$branchIds[($i - 1) % count($branchIds)]]->values();
            $slot = intdiv($i - 1, count($branchIds));
            $student = ['id' => $studentId, 'reliability' => 0.55 + mt_rand(0, 43) / 100];

            $joined = [$branchGroups[$slot % $branchGroups->count()]];

            // Roughly one student in ten attends a second group.
            if ($i % 10 === 0 && $branchGroups->count() > 1) {
                $joined[] = $branchGroups[($slot + 7) % $branchGroups->count()];
            }

            foreach (collect($joined)->unique('id') as $group) {
                $pivot[] = ['study_group_id' => $group['id'], 'student_id' => $studentId, 'joined_at' => now()->subDays($this->days)->toDateTimeString()];
                $members[$group['id']][] = $student;
            }

            if (count($pivot) >= self::CHUNK) {
                $this->flush('group_student', $pivot);
            }
        }

        $this->flush('group_student', $pivot);

        return $members;
    }

    /**
     * @param  list<array{id: int, branch_id: int, teacher_id: int, days: list<int>}>  $groups
     * @param  array<int, list<array{id: int, reliability: float}>>  $members
     */
    private function createAttendance(array $groups, array $members): void
    {
        $today = CarbonImmutable::today();
        $rows = [];

        foreach ($groups as $group) {
            $lessonDates = [];

            for ($offset = $this->days; $offset >= 1; $offset--) {
                $date = $today->subDays($offset);

                if (in_array($date->dayOfWeekIso, $group['days'], true)) {
                    $lessonDates[] = $date->toDateString();
                }
            }

            foreach ($lessonDates as $date) {
                foreach ($members[$group['id']] ?? [] as $student) {
                    $rows[] = [
                        'branch_id' => $group['branch_id'],
                        'study_group_id' => $group['id'],
                        'student_id' => $student['id'],
                        'marked_by' => $group['teacher_id'],
                        'lesson_date' => $date,
                        'status' => $this->attendanceStatus($student['reliability']),
                        'created_at' => $this->now,
                        'updated_at' => $this->now,
                    ];

                    if (count($rows) === self::CHUNK) {
                        $this->flush('attendances', $rows);
                    }
                }
            }
        }

        $this->flush('attendances', $rows);
    }

    /**
     * @param  list<array{id: int, branch_id: int, teacher_id: int, days: list<int>}>  $groups
     * @param  array<int, list<array{id: int, reliability: float}>>  $members
     */
    private function createSubmissions(array $groups, array $members): void
    {
        $now = CarbonImmutable::now();
        $firstId = (int) DB::table('assignments')->max('id');
        $weekGap = max(1, intdiv($this->days, $this->assignmentsPerGroup));
        $rows = [];

        foreach ($groups as $group) {
            for ($n = 0; $n < $this->assignmentsPerGroup; $n++) {
                $published = $now->subDays($this->days - $n * $weekGap)->setTime(10, 0);

                if ($published->isFuture()) {
                    break;
                }

                $due = $published->addDays(4);

                $rows[] = [
                    'branch_id' => $group['branch_id'],
                    'study_group_id' => $group['id'],
                    'teacher_id' => $group['teacher_id'],
                    'title' => 'Uy vazifasi '.($n + 1),
                    'description' => 'Darslikdagi mashqlarni bajaring va natijani yuklang.',
                    'max_score' => 100,
                    'due_at' => $due->toDateTimeString(),
                    'status' => $due->lt($now->subDays(30)) ? AssignmentStatus::Closed->value : AssignmentStatus::Published->value,
                    'published_at' => $published->toDateTimeString(),
                    'created_at' => $published->toDateTimeString(),
                    'updated_at' => $published->toDateTimeString(),
                ];

                if (count($rows) === self::CHUNK) {
                    $this->flush('assignments', $rows);
                }
            }
        }

        $this->flush('assignments', $rows);

        $teachers = collect($groups)->pluck('teacher_id', 'id');

        DB::table('assignments')->where('id', '>', $firstId)->orderBy('id')
            ->select(['id', 'branch_id', 'study_group_id', 'published_at', 'due_at'])
            ->chunkById(500, function ($assignments) use ($members, $teachers, $now, &$rows) {
                foreach ($assignments as $assignment) {
                    $published = CarbonImmutable::parse($assignment->published_at);
                    $due = CarbonImmutable::parse($assignment->due_at);

                    foreach ($members[$assignment->study_group_id] ?? [] as $student) {
                        if (mt_rand(1, 100) > 85) {
                            continue;
                        }

                        $submittedAt = $published->addMinutes(mt_rand(60, 6 * 24 * 60));

                        if ($submittedAt->isFuture()) {
                            continue;
                        }

                        $graded = $submittedAt->lt($now->subDays(3)) && mt_rand(1, 100) <= 75;

                        $rows[] = [
                            'branch_id' => $assignment->branch_id,
                            'assignment_id' => $assignment->id,
                            'student_id' => $student['id'],
                            'body' => 'Vazifa bajarildi.',
                            'status' => ($graded ? SubmissionStatus::Graded : SubmissionStatus::Submitted)->value,
                            'is_late' => $submittedAt->gt($due),
                            'submitted_at' => $submittedAt->toDateTimeString(),
                            'score' => $graded ? mt_rand(45, 100) : null,
                            'feedback' => null,
                            'graded_by' => $graded ? $teachers[$assignment->study_group_id] : null,
                            'graded_at' => $graded ? $submittedAt->addDays(mt_rand(1, 3))->min($now)->toDateTimeString() : null,
                            'created_at' => $submittedAt->toDateTimeString(),
                            'updated_at' => $submittedAt->toDateTimeString(),
                        ];

                        if (count($rows) === self::CHUNK) {
                            $this->flush('submissions', $rows);
                        }
                    }
                }
            });

        $this->flush('submissions', $rows);
    }

    private function attendanceStatus(float $reliability): string
    {
        if (mt_rand() / mt_getrandmax() < $reliability) {
            return AttendanceStatus::Present->value;
        }

        return match (mt_rand(1, 5)) {
            1, 2 => AttendanceStatus::Late->value,
            3, 4 => AttendanceStatus::Absent->value,
            default => AttendanceStatus::Excused->value,
        };
    }

    private function user(string $name, string $email, Role $role, string $phone, int $branchId): array
    {
        return [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'email_verified_at' => $this->now,
            'password' => $this->password,
            'role' => $role->value,
            'status' => UserStatus::Active->value,
            'branch_id' => $branchId,
            'birth_date' => now()->subYears(mt_rand(10, 40))->subDays(mt_rand(0, 364))->toDateString(),
            'created_at' => now()->subDays(mt_rand(0, $this->days))->toDateTimeString(),
            'updated_at' => $this->now,
        ];
    }

    private function name(int $index): string
    {
        $first = ['Aziz', 'Malika', 'Jasur', 'Dilnoza', 'Bobur', 'Madina', 'Sardor', 'Zilola', 'Otabek', 'Nodira', 'Javohir', 'Shahzoda', 'Akmal', 'Gulnora', 'Rustam', 'Sevara'];
        $last = ['Karimov', 'Yusupova', 'Rahimov', 'Tursunova', 'Abdullayev', 'Nazarova', 'Ergashev', 'Sobirova', 'Qodirov', 'Ismoilova', 'Xolmatov', 'Aliyeva'];

        return $first[$index % count($first)].' '.$last[intdiv($index, count($first)) % count($last)];
    }

    /**
     * Unique per ($kind, $index): +998 9K NNN NN NN.
     */
    private function phone(int $kind, int $index): string
    {
        return sprintf('+998 9%d %03d %02d %02d', $kind, intdiv($index, 10000) % 1000, intdiv($index, 100) % 100, $index % 100);
    }

    private function flush(string $table, array &$rows): void
    {
        if ($rows !== []) {
            DB::table($table)->insert($rows);
            $rows = [];
        }
    }
}

<?php

namespace Database\Seeders;

use App\Enums\AssignmentStatus;
use App\Enums\GroupStatus;
use App\Enums\Role;
use App\Enums\SubmissionStatus;
use App\Enums\UserStatus;
use App\Models\Assignment;
use App\Models\Branch;
use App\Models\Course;
use App\Models\StudyGroup;
use App\Models\Submission;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\Data\UzbekNames;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * The real Chirchiq branch: staff, 13 groups on the actual timetable, 104 Uzbek-named students
 * (or the roster in storage/app/private/students.csv), three assignments per group and
 * submissions. All passwords are "password". Safe to run repeatedly (everything is
 * firstOrCreate-d), and not part of DatabaseSeeder:
 *
 *   vendor/bin/sail artisan db:seed --class=ChirchiqSeeder
 *
 * Seeders have no authenticated user, so branch_id is always set explicitly and
 * the branch scope is bypassed with withoutGlobalScopes().
 *
 * Roster CSV columns: full_name ("Surname FirstName"), phone, birth_date, group_name
 * (one of the generated group names). Phones are never printed.
 */
class ChirchiqSeeder extends Seeder
{
    private const EMAIL_DOMAIN = 'brainup.uz';

    private const STUDENTS_PER_GROUP = 8;

    /** @var array<string, array{name: string, email: string}> */
    private const TEACHERS = [
        'utkirbek' => ['name' => 'Tulqinov Utkirbek', 'email' => 'utkirbek@brainup.uz'],
        'robiya' => ['name' => 'Yunusova Robiya', 'email' => 'robiya@brainup.uz'],
        'olimjon' => ['name' => 'Mamurov Olimjon', 'email' => 'olimjon@brainup.uz'],
        'sabina' => ['name' => 'Axmedova Sabina', 'email' => 'sabina@brainup.uz'],
    ];

    /** @var array<string, array{name: string, label: string, ages: array{int, int}}> */
    private const COURSES = [
        'english' => ['name' => 'Ingliz tili', 'label' => 'English', 'ages' => [12, 20]],
        'it' => ['name' => 'IT', 'label' => 'IT', 'ages' => [10, 18]],
        'math' => ['name' => 'Matematika', 'label' => 'Math', 'ages' => [12, 20]],
    ];

    /** @var array<string, array{days: list<string>, label: string}> */
    private const DAY_SETS = [
        'A' => ['days' => ['tue', 'thu', 'sat'], 'label' => 'Se-Pa-Sh'],
        'B' => ['days' => ['mon', 'wed', 'fri'], 'label' => 'Du-Ch-Ju'],
    ];

    /** @var list<array{string, string, string, list<string>}> teacher, course, day set, times */
    private const TIMETABLE = [
        ['utkirbek', 'english', 'A', ['09:00', '14:00', '15:30', '17:00']],
        ['robiya', 'english', 'A', ['15:30', '17:00']],
        ['utkirbek', 'it', 'B', ['14:00', '15:30', '17:00']],
        ['olimjon', 'math', 'B', ['14:00', '15:30', '18:30']],
        ['sabina', 'english', 'B', ['15:30']],
    ];

    private const ASSIGNMENT_IDEAS = [
        'english' => [
            'Present Simple mashqlari',
            'Unit 3 so‘zlarini yodlash',
            'Essay: My Family',
            'Past Simple: hikoya yozish',
            'Reading: matnni tarjima qilish',
        ],
        'it' => [
            'Kompyuter qismlari',
            'Word’da hujjat yaratish',
            'Excel’da jadval',
            'HTML: birinchi sahifa',
            'Klaviaturada tez yozish',
        ],
        'math' => [
            'Kasrlar ustida amallar',
            'Tenglamalar yechish',
            'Foizlarga oid masalalar',
            'Geometriya: yuzalar',
            'Matnli masalalar',
        ],
    ];

    private const BODIES = [
        'Vazifani bajardim, tekshirib ko‘ring.',
        'Barcha mashqlarni yechdim, natijani yuboryapman.',
        'Ustoz, vazifa tayyor. Fayl ilova qilindi.',
        'Mavzuni takrorlab, vazifani to‘liq bajardim.',
        'Vazifani o‘z vaqtida topshiryapman.',
    ];

    private const FEEDBACK_HIGH = ['Zo‘r natija, barakalla!', 'Juda yaxshi ish, davom eting!', 'Mukammal bajarilgan.'];

    private const FEEDBACK_MID = ['Yaxshi ish, ozgina e’tiborliroq bo‘ling.', 'Yaxshi, lekin ba’zi xatolar bor.', 'Qoniqarli, mavzuni yana takrorlang.'];

    private const FEEDBACK_LOW = ['Xatolarni qayta ko‘rib chiqing.', 'Mavzuni yaxshiroq o‘rganish kerak.', 'Ko‘proq mashq qiling.'];

    private const PHONE_OPERATORS = ['90', '91', '93', '94', '97', '99', '88', '33'];

    /** Overrides the default storage/app/private/students.csv (used by tests). */
    public ?string $rosterPath = null;

    /** @var array<string, true> */
    private array $usedEmails = [];

    /** @var array<string, true> */
    private array $usedPhones = [];

    /** @var array<string, true> */
    private array $usedNames = [];

    public function run(): void
    {
        mt_srand(2026);

        // A fresh run must generate the same people, even on a reused seeder instance.
        $this->usedEmails = $this->usedPhones = $this->usedNames = [];

        $this->call([GamificationSeeder::class]);

        DB::transaction(function () {
            $branch = Branch::firstOrCreate(['name' => 'Chirchiq filiali'], ['is_active' => true]);
            $teachers = $this->createStaff($branch);
            $courses = $this->createCourses();
            $groups = $this->createGroups($branch, $teachers, $courses);

            $this->createStudents($branch, $groups);
            $this->createAssignments($branch, $groups);
        });

        $branch = Branch::where('name', 'Chirchiq filiali')->firstOrFail();

        Artisan::call('gamification:rebuild', ['--branch' => $branch->id]);

        $this->command?->info(sprintf(
            'Chirchiq filiali tayyor: %d guruh, %d o‘quvchi, %d vazifa.',
            StudyGroup::withoutGlobalScopes()->where('branch_id', $branch->id)->count(),
            User::students()->inBranch($branch->id)->count(),
            Assignment::withoutGlobalScopes()->where('branch_id', $branch->id)->count(),
        ));
    }

    /**
     * @return array<string, User> teachers by key
     */
    private function createStaff(Branch $branch): array
    {
        $this->staff('Super Admin', 'admin@'.self::EMAIL_DOMAIN, Role::SuperAdmin);
        $this->staff('Karimov Sherzod', 'filial.admin@'.self::EMAIL_DOMAIN, Role::BranchAdmin, $branch->id);

        $teachers = [];

        foreach (self::TEACHERS as $key => $data) {
            $teacher = $this->staff($data['name'], $data['email'], Role::Teacher);
            $teacher->branches()->syncWithoutDetaching([$branch->id]);

            $teachers[$key] = $teacher;
        }

        return $teachers;
    }

    private function staff(string $name, string $email, Role $role, ?int $branchId = null): User
    {
        return User::unguarded(fn () => User::firstOrCreate(['email' => $email], [
            'name' => $name,
            'password' => 'password',
            'role' => $role,
            'status' => UserStatus::Active,
            'branch_id' => $branchId,
            'email_verified_at' => now(),
        ]));
    }

    /**
     * @return array<string, Course> courses by slug
     */
    private function createCourses(): array
    {
        $courses = [];

        foreach (self::COURSES as $slug => $data) {
            $courses[$slug] = Course::firstOrCreate(['slug' => $slug], ['name' => $data['name'], 'is_active' => true]);
        }

        return $courses;
    }

    /**
     * @param  array<string, User>  $teachers
     * @param  array<string, Course>  $courses
     * @return list<array{group: StudyGroup, name: string, course: string, teacher: string}>
     */
    private function createGroups(Branch $branch, array $teachers, array $courses): array
    {
        $groups = [];
        $slots = [];
        $numbers = [];

        foreach (self::TIMETABLE as [$teacherKey, $courseSlug, $daySet, $times]) {
            foreach ($times as $time) {
                $slot = "{$teacherKey}|{$daySet}|{$time}";

                if (isset($slots[$slot])) {
                    throw new LogicException("Bir o‘qituvchida bir xil kun va vaqtda ikkita guruh bor: {$slot}");
                }

                $slots[$slot] = true;

                // "<Course>-<Teacher>-<n>", numbered per teacher and course in timetable order: English-Utkirbek-1.
                $number = $numbers["{$teacherKey}|{$courseSlug}"] = ($numbers["{$teacherKey}|{$courseSlug}"] ?? 0) + 1;
                $name = sprintf('%s-%s-%d', self::COURSES[$courseSlug]['label'], $this->firstName(self::TEACHERS[$teacherKey]['name']), $number);

                $this->renameLegacyGroup($branch, $teachers[$teacherKey], $courses[$courseSlug], $time, $name);

                $group = StudyGroup::withoutGlobalScopes()->firstOrCreate(
                    ['branch_id' => $branch->id, 'name' => $name],
                    [
                        'course_id' => $courses[$courseSlug]->id,
                        'teacher_id' => $teachers[$teacherKey]->id,
                        'starts_on' => now()->startOfMonth()->toDateString(),
                        'schedule' => ['days' => self::DAY_SETS[$daySet]['days'], 'time' => $time],
                        'capacity' => 15,
                        'status' => GroupStatus::Active,
                    ]
                );

                $groups[] = ['group' => $group, 'name' => $name, 'course' => $courseSlug, 'teacher' => $teacherKey];
            }
        }

        return $groups;
    }

    /**
     * Groups seeded earlier were named "<Fan> <HH:MM> (<days>)"; give them the new name instead of creating duplicates.
     * Names an admin has changed by hand are left alone.
     */
    private function renameLegacyGroup(Branch $branch, User $teacher, Course $course, string $time, string $name): void
    {
        if (StudyGroup::withoutGlobalScopes()->where('branch_id', $branch->id)->where('name', $name)->exists()) {
            return;
        }

        StudyGroup::withoutGlobalScopes()
            ->where('branch_id', $branch->id)
            ->where('teacher_id', $teacher->id)
            ->where('course_id', $course->id)
            ->where('schedule->time', $time)
            ->get()
            ->first(fn (StudyGroup $group) => preg_match('/^(Ingliz tili|IT|Matematika) \d{2}:\d{2} \((Se-Pa-Sh|Du-Ch-Ju)\)( \w+)?$/u', $group->name) === 1)
            ?->update(['name' => $name]);
    }

    /**
     * "Yunusova Robiya" → "Robiya" (names are written "<Surname> <FirstName>").
     */
    private function firstName(string $fullName): string
    {
        return trim(mb_substr($fullName, (int) mb_strpos($fullName, ' ')));
    }

    /**
     * @param  list<array{group: StudyGroup, name: string, course: string, teacher: string}>  $groups
     */
    private function createStudents(Branch $branch, array $groups): void
    {
        $roster = $this->rosterFile();

        $roster === null
            ? $this->createGeneratedStudents($branch, $groups)
            : $this->createRosterStudents($branch, $groups, $roster);
    }

    /**
     * @param  list<array{group: StudyGroup, name: string, course: string, teacher: string}>  $groups
     */
    private function createGeneratedStudents(Branch $branch, array $groups): void
    {
        $position = 0;

        foreach ($groups as $entry) {
            [$minAge, $maxAge] = self::COURSES[$entry['course']]['ages'];
            $ids = [];

            for ($i = 0; $i < self::STUDENTS_PER_GROUP; $i++) {
                $male = $position++ % 2 === 0;
                [$fullName, $surname, $first] = $this->uniquePerson($male);

                $student = $this->student($branch, [
                    'name' => $fullName,
                    'email' => $this->uniqueEmail($first, $surname),
                    'phone' => $this->uniquePhone(),
                    'birth_date' => now()->subYears(mt_rand($minAge, $maxAge))->subDays(mt_rand(0, 364))->toDateString(),
                ]);

                $ids[] = $student->id;
            }

            $entry['group']->students()->syncWithoutDetaching($ids);
        }
    }

    /**
     * @return array{string, string, string} full name, surname, first name
     */
    private function uniquePerson(bool $male): array
    {
        $firstNames = $male ? UzbekNames::MALE_FIRST_NAMES : UzbekNames::FEMALE_FIRST_NAMES;

        do {
            $first = $firstNames[mt_rand(0, count($firstNames) - 1)];
            $root = UzbekNames::SURNAME_ROOTS[mt_rand(0, count(UzbekNames::SURNAME_ROOTS) - 1)];
            $fullName = UzbekNames::fullName($root, $first, $male);
        } while (isset($this->usedNames[$fullName]));

        $this->usedNames[$fullName] = true;

        return [$fullName, UzbekNames::surname($root, $male), $first];
    }

    private function uniqueEmail(string $first, string $surname, ?int $digits = null): string
    {
        $local = UzbekNames::slug($first).'.'.UzbekNames::slug($surname);
        $digits ??= mt_rand(10, 99);

        do {
            $email = sprintf('%s%02d@%s', $local, $digits, self::EMAIL_DOMAIN);
            $digits = $digits >= 99 ? 10 : $digits + 1;
        } while (isset($this->usedEmails[$email]));

        $this->usedEmails[$email] = true;

        return $email;
    }

    private function uniquePhone(): string
    {
        do {
            $phone = '+998'.self::PHONE_OPERATORS[mt_rand(0, count(self::PHONE_OPERATORS) - 1)].mt_rand(1000000, 9999999);
        } while (isset($this->usedPhones[$phone]));

        $this->usedPhones[$phone] = true;

        return $phone;
    }

    /**
     * @param  array{name: string, email: string, phone: ?string, birth_date: ?string}  $attributes
     */
    private function student(Branch $branch, array $attributes): User
    {
        return User::unguarded(fn () => User::firstOrCreate(['email' => $attributes['email']], [
            ...$attributes,
            'password' => 'password',
            'role' => Role::Student,
            'status' => UserStatus::Active,
            'branch_id' => $branch->id,
            'email_verified_at' => now(),
        ]));
    }

    private function rosterFile(): ?string
    {
        $path = $this->rosterPath ?? storage_path('app/private/students.csv');

        return is_file($path) ? $path : null;
    }

    /**
     * @param  list<array{group: StudyGroup, name: string, course: string, teacher: string}>  $groups
     */
    private function createRosterStudents(Branch $branch, array $groups, string $path): void
    {
        $byName = collect($groups)->keyBy(fn (array $entry) => mb_strtolower($entry['name']));
        $handle = fopen($path, 'r');
        $header = null;
        $line = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $line++;

            if ($header === null) {
                $header = array_map(fn ($column) => trim(ltrim((string) $column, "\xEF\xBB\xBF")), $row);

                continue;
            }

            if (count($row) !== count($header)) {
                $skipped++;

                continue;
            }

            $data = array_combine($header, array_map('trim', $row));
            $entry = $byName->get(mb_strtolower($data['group_name'] ?? ''));
            $fullName = $data['full_name'] ?? '';

            if ($entry === null || $fullName === '') {
                $this->command?->warn("Qator {$line} o‘tkazib yuborildi (guruh yoki ism topilmadi).");
                $skipped++;

                continue;
            }

            [$surname, $first] = array_pad(explode(' ', $fullName, 2), 2, '');

            $student = $this->student($branch, [
                'name' => $fullName,
                'email' => $this->uniqueEmail($first !== '' ? $first : $surname, $surname, crc32($fullName.'|'.($data['phone'] ?? '')) % 90 + 10),
                'phone' => ($data['phone'] ?? '') !== '' ? $data['phone'] : null,
                'birth_date' => ($data['birth_date'] ?? '') !== '' ? $data['birth_date'] : null,
            ]);

            $entry['group']->students()->syncWithoutDetaching([$student->id]);
        }

        fclose($handle);

        if ($skipped > 0) {
            $this->command?->warn("Ro‘yxatdan {$skipped} ta qator o‘tkazib yuborildi.");
        }
    }

    /**
     * @param  list<array{group: StudyGroup, name: string, course: string, teacher: string}>  $groups
     */
    private function createAssignments(Branch $branch, array $groups): void
    {
        $now = CarbonImmutable::now();

        foreach ($groups as $index => $entry) {
            $group = $entry['group'];
            $teacherId = $group->teacher_id;
            $students = $group->students()->orderBy('users.id')->pluck('users.id')->all();
            $ideas = self::ASSIGNMENT_IDEAS[$entry['course']];

            $slots = [
                [
                    'title' => $ideas[$index % 5],
                    'status' => AssignmentStatus::Closed,
                    'published_at' => $now->subDays(14),
                    'due_at' => $now->subDays(7),
                    'share' => 0.7,
                ],
                [
                    'title' => $ideas[($index + 1) % 5],
                    'status' => AssignmentStatus::Published,
                    'published_at' => $now->subDay(),
                    'due_at' => $now->addDays(5),
                    'share' => 0.3,
                ],
                [
                    'title' => $ideas[($index + 2) % 5],
                    'status' => AssignmentStatus::Draft,
                    'published_at' => null,
                    'due_at' => $now->addDays(14),
                    'share' => 0.0,
                ],
            ];

            foreach ($slots as $slot) {
                $assignment = Assignment::unguarded(fn () => Assignment::withoutGlobalScopes()->firstOrCreate(
                    ['study_group_id' => $group->id, 'title' => $slot['title']],
                    [
                        'branch_id' => $branch->id,
                        'teacher_id' => $teacherId,
                        'description' => 'Mavzu bo‘yicha topshiriqni diqqat bilan bajaring va tayyor ishni muddatgacha yuboring.',
                        'max_score' => 100,
                        'due_at' => $slot['due_at'],
                        'status' => $slot['status'],
                        'published_at' => $slot['published_at'],
                        'created_at' => $slot['published_at'] ?? $now->subDays(2),
                        'updated_at' => $slot['published_at'] ?? $now->subDays(2),
                    ]
                ));

                $this->createSubmissions($branch, $assignment, $slot, $students, $teacherId, $now);
            }
        }
    }

    /**
     * @param  array{status: AssignmentStatus, published_at: ?CarbonImmutable, due_at: CarbonImmutable, share: float}  $slot
     * @param  list<int>  $students
     */
    private function createSubmissions(Branch $branch, Assignment $assignment, array $slot, array $students, int $teacherId, CarbonImmutable $now): void
    {
        if ($slot['share'] === 0.0 || $students === []) {
            return;
        }

        $closed = $slot['status'] === AssignmentStatus::Closed;
        $count = (int) round(count($students) * $slot['share']);

        foreach ($this->pick($students, $count) as $studentId) {
            $late = $closed && mt_rand(1, 100) <= 15;
            $score = $closed ? mt_rand(50, 100) : null;

            // Times come from the assignment as stored, so a re-run leaves the data unchanged.
            $published = CarbonImmutable::parse($assignment->published_at);
            $due = CarbonImmutable::parse($assignment->due_at);

            $submittedAt = match (true) {
                ! $closed => $now->subHours(mt_rand(1, 20)),
                $late => $due->addHours(mt_rand(3, 30)),
                default => $published->addHours(mt_rand(3, 6 * 24 + 20)),
            };

            Submission::unguarded(fn () => Submission::withoutGlobalScopes()->firstOrCreate(
                ['assignment_id' => $assignment->id, 'student_id' => $studentId],
                [
                    'branch_id' => $branch->id,
                    'body' => self::BODIES[mt_rand(0, count(self::BODIES) - 1)],
                    'status' => $closed ? SubmissionStatus::Graded : SubmissionStatus::Submitted,
                    'is_late' => $late,
                    'submitted_at' => $submittedAt,
                    'score' => $score,
                    'feedback' => $closed ? $this->feedback($score) : null,
                    'graded_by' => $closed ? $teacherId : null,
                    'graded_at' => $closed ? $submittedAt->addDay() : null,
                    'created_at' => $submittedAt,
                    'updated_at' => $submittedAt,
                ]
            ));
        }
    }

    private function feedback(int $score): string
    {
        $pool = match (true) {
            $score >= 85 => self::FEEDBACK_HIGH,
            $score >= 70 => self::FEEDBACK_MID,
            default => self::FEEDBACK_LOW,
        };

        return $pool[mt_rand(0, count($pool) - 1)];
    }

    /**
     * @param  list<int>  $items
     * @return list<int>
     */
    private function pick(array $items, int $count): array
    {
        for ($i = count($items) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            [$items[$i], $items[$j]] = [$items[$j], $items[$i]];
        }

        return array_slice($items, 0, $count);
    }
}

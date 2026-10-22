<?php

namespace Tests\Feature;

use App\Enums\AssignmentStatus;
use App\Enums\Role;
use App\Enums\SubmissionStatus;
use App\Models\Assignment;
use App\Models\Branch;
use App\Models\StudyGroup;
use App\Models\Submission;
use App\Models\User;
use Database\Seeders\ChirchiqSeeder;
use Database\Seeders\Data\UzbekNames;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChirchiqSeederTest extends TestCase
{
    use RefreshDatabase;

    private function seedChirchiq(?string $roster = null): void
    {
        $seeder = app(ChirchiqSeeder::class)->setContainer(app());
        // Never pick up a real roster that may exist on a developer machine.
        $seeder->rosterPath = $roster ?? sys_get_temp_dir().'/no-such-students.csv';
        $seeder->run();
    }

    private function chirchiqGroups()
    {
        return StudyGroup::withoutGlobalScopes()->with(['teacher', 'course'])->withCount('students')->orderBy('id')->get();
    }

    public function test_running_twice_creates_no_duplicates(): void
    {
        $this->seedChirchiq();
        $first = [User::count(), StudyGroup::withoutGlobalScopes()->count(), Assignment::withoutGlobalScopes()->count(), Submission::withoutGlobalScopes()->count()];

        $this->seedChirchiq();

        $this->assertSame(1, Branch::where('name', 'Chirchiq filiali')->count());
        $this->assertSame(4, User::teachers()->count());
        $this->assertSame(13, StudyGroup::withoutGlobalScopes()->count());
        $this->assertSame(104, User::students()->count());
        $this->assertSame(1, User::where('email', 'admin@brainup.uz')->where('role', Role::SuperAdmin->value)->count());
        $this->assertSame(1, User::where('email', 'filial.admin@brainup.uz')->where('role', Role::BranchAdmin->value)->count());
        $this->assertSame(
            $first,
            [User::count(), StudyGroup::withoutGlobalScopes()->count(), Assignment::withoutGlobalScopes()->count(), Submission::withoutGlobalScopes()->count()]
        );
    }

    public function test_groups_have_the_real_timetable(): void
    {
        $this->seedChirchiq();

        $chirchiq = Branch::where('name', 'Chirchiq filiali')->firstOrFail();
        $groups = $this->chirchiqGroups();

        $this->assertCount(13, $groups);

        $summary = $groups->map(fn (StudyGroup $group) => [
            explode(' ', $group->teacher->name)[1],
            $group->course->slug,
            implode(',', $group->schedule['days']),
            $group->schedule['time'],
        ])->all();

        $a = 'tue,thu,sat';
        $b = 'mon,wed,fri';

        $this->assertEqualsCanonicalizing([
            ['Utkirbek', 'english', $a, '09:00'], ['Utkirbek', 'english', $a, '14:00'],
            ['Utkirbek', 'english', $a, '15:30'], ['Utkirbek', 'english', $a, '17:00'],
            ['Robiya', 'english', $a, '15:30'], ['Robiya', 'english', $a, '17:00'],
            ['Utkirbek', 'it', $b, '14:00'], ['Utkirbek', 'it', $b, '15:30'], ['Utkirbek', 'it', $b, '17:00'],
            ['Olimjon', 'math', $b, '14:00'], ['Olimjon', 'math', $b, '15:30'], ['Olimjon', 'math', $b, '18:30'],
            ['Sabina', 'english', $b, '15:30'],
        ], $summary);

        foreach ($groups as $group) {
            $this->assertSame($chirchiq->id, $group->branch_id);
            $this->assertSame(15, $group->capacity);
            $this->assertSame('active', $group->status->value);
            $this->assertSame(now()->startOfMonth()->toDateString(), $group->starts_on->toDateString());
            $this->assertSame(8, $group->students_count);
            $this->assertTrue($group->teacher->branches()->whereKey($chirchiq->id)->exists());
            $this->assertSame(3, Assignment::withoutGlobalScopes()->where('study_group_id', $group->id)->count());
        }

        // Group names are "<Course>-<Teacher>-<n>", numbered per teacher and course in timetable order.
        $this->assertSame([
            'English-Utkirbek-1', 'English-Utkirbek-2', 'English-Utkirbek-3', 'English-Utkirbek-4',
            'English-Robiya-1', 'English-Robiya-2',
            'IT-Utkirbek-1', 'IT-Utkirbek-2', 'IT-Utkirbek-3',
            'Math-Olimjon-1', 'Math-Olimjon-2', 'Math-Olimjon-3',
            'English-Sabina-1',
        ], $groups->pluck('name')->all());

        $this->assertSame('09:00', $groups->firstWhere('name', 'English-Utkirbek-1')->schedule['time']);
        $this->assertSame('17:00', $groups->firstWhere('name', 'English-Utkirbek-4')->schedule['time']);
        $this->assertSame('18:30', $groups->firstWhere('name', 'Math-Olimjon-3')->schedule['time']);
    }

    public function test_groups_seeded_with_the_old_names_are_renamed_not_duplicated(): void
    {
        $this->seedChirchiq();

        $ids = StudyGroup::withoutGlobalScopes()->orderBy('id')->pluck('name', 'id')->all();

        $legacy = [
            'English-Utkirbek-1' => 'Ingliz tili 09:00 (Se-Pa-Sh)',
            'English-Robiya-1' => 'Ingliz tili 15:30 (Se-Pa-Sh) Robiya',
            'Math-Olimjon-3' => 'Matematika 18:30 (Du-Ch-Ju)',
        ];

        foreach ($legacy as $new => $old) {
            StudyGroup::withoutGlobalScopes()->where('name', $new)->update(['name' => $old]);
        }

        // A name an admin chose by hand is not recognised as a seeded group, so it is left as it is.
        StudyGroup::withoutGlobalScopes()->where('name', 'IT-Utkirbek-2')->update(['name' => 'Qo‘lda o‘zgartirilgan nom']);

        $this->seedChirchiq();

        $after = StudyGroup::withoutGlobalScopes()->orderBy('id')->pluck('name', 'id')->all();

        foreach ($ids as $id => $name) {
            $this->assertSame($name === 'IT-Utkirbek-2' ? 'Qo‘lda o‘zgartirilgan nom' : $name, $after[$id]);
        }

        // Only the hand-renamed group gets a fresh seeded twin.
        $this->assertCount(14, $after);
        $this->assertSame(1, collect($after)->filter(fn ($name) => $name === 'IT-Utkirbek-2')->count());
    }

    public function test_no_teacher_has_two_groups_at_the_same_day_and_time(): void
    {
        $this->seedChirchiq();

        $slots = $this->chirchiqGroups()->map(fn (StudyGroup $group) => $group->teacher_id.'|'.implode(',', $group->schedule['days']).'|'.$group->schedule['time']);

        $this->assertSame($slots->count(), $slots->unique()->count());
    }

    public function test_students_look_like_real_uzbek_people(): void
    {
        $this->seedChirchiq();

        $students = User::students()->get();

        $this->assertSame($students->count(), $students->pluck('name')->unique()->count());
        $this->assertSame($students->count(), $students->pluck('email')->unique()->count());
        $this->assertSame($students->count(), $students->pluck('phone')->unique()->count());

        foreach ($students as $student) {
            [$surname, $first] = explode(' ', $student->name, 2);
            $male = in_array($first, UzbekNames::MALE_FIRST_NAMES, true);

            $this->assertTrue($male || in_array($first, UzbekNames::FEMALE_FIRST_NAMES, true), "Noma’lum ism: {$student->name}");
            $this->assertContains(
                $surname,
                array_map(fn (string $root) => UzbekNames::surname($root, $male), UzbekNames::SURNAME_ROOTS),
                "Familiya ismga mos emas: {$student->name}"
            );
            $this->assertMatchesRegularExpression('/^\+998\d{9}$/', $student->phone);
            $this->assertMatchesRegularExpression('/^[a-z]+\.[a-z]+\d{2}@brainup\.uz$/', $student->email);
            $this->assertSame(Role::Student, $student->role);
            $this->assertNotNull($student->email_verified_at);
        }

        $this->assertEqualsWithDelta(52, $students->filter(fn (User $student) => in_array(explode(' ', $student->name, 2)[1], UzbekNames::MALE_FIRST_NAMES, true))->count(), 1);
    }

    public function test_student_ages_match_the_course(): void
    {
        $this->seedChirchiq();

        foreach ($this->chirchiqGroups() as $group) {
            [$min, $max] = $group->course->slug === 'it' ? [10, 18] : [12, 20];

            foreach ($group->students as $student) {
                $age = (int) $student->birth_date->diffInYears(now());

                $this->assertGreaterThanOrEqual($min, $age);
                $this->assertLessThanOrEqual($max, $age);
            }
        }
    }

    public function test_assignments_and_submissions_follow_the_plan(): void
    {
        $this->seedChirchiq();

        foreach ($this->chirchiqGroups() as $group) {
            $assignments = Assignment::withoutGlobalScopes()->where('study_group_id', $group->id)->get()->keyBy(fn (Assignment $a) => $a->status->value);

            $this->assertEqualsCanonicalizing(['closed', 'published', 'draft'], $assignments->keys()->all());

            foreach ($assignments as $assignment) {
                $this->assertSame($group->teacher_id, $assignment->teacher_id);
                $this->assertSame($group->branch_id, $assignment->branch_id);
                $this->assertSame(100, $assignment->max_score);
            }

            $closed = $assignments['closed'];
            $this->assertTrue($closed->published_at->isPast() && $closed->due_at->isPast());
            $this->assertSame(6, $closed->submissions()->withoutGlobalScopes()->count());
            $this->assertSame(6, $closed->submissions()->withoutGlobalScopes()->where('status', SubmissionStatus::Graded->value)->count());

            foreach ($closed->submissions()->withoutGlobalScopes()->get() as $submission) {
                $this->assertGreaterThanOrEqual(50, $submission->score);
                $this->assertNotEmpty($submission->feedback);
                $this->assertNotEmpty($submission->body);
                $this->assertSame($group->branch_id, $submission->branch_id);
            }

            $published = $assignments['published'];
            $this->assertTrue($published->due_at->isFuture());
            $this->assertSame(2, $published->submissions()->withoutGlobalScopes()->where('status', SubmissionStatus::Submitted->value)->count());

            $draft = $assignments['draft'];
            $this->assertNull($draft->published_at);
            $this->assertSame(AssignmentStatus::Draft, $draft->status);
            $this->assertSame(0, $draft->submissions()->withoutGlobalScopes()->count());
        }

        $this->assertGreaterThan(0, Submission::withoutGlobalScopes()->where('is_late', true)->count());
    }

    public function test_gamification_is_synced_for_the_branch(): void
    {
        $this->seedChirchiq();

        $this->assertGreaterThan(0, User::students()->where('xp', '>', 0)->count());
        $this->assertGreaterThan(0, User::students()->whereNotNull('level_id')->count());
    }

    public function test_it_is_not_part_of_the_default_seeding(): void
    {
        $this->assertStringNotContainsString('ChirchiqSeeder', file_get_contents((new \ReflectionClass(DatabaseSeeder::class))->getFileName()));
    }

    public function test_it_builds_students_from_a_roster_csv_when_present(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'roster');
        file_put_contents($path, "full_name,phone,birth_date,group_name\n".
            "Karimova Madina,+998901112233,2011-04-02,Math-Olimjon-3\n".
            "Jo‘rayev Ulug‘bek,+998931234567,2009-09-15,Math-Olimjon-3\n".
            "Noma’lum Talaba,+998971111111,2010-01-01,Mavjud-emas-9\n");

        $this->seedChirchiq($path);
        $this->seedChirchiq($path);
        unlink($path);

        $students = User::students()->orderBy('id')->get();

        $this->assertCount(2, $students);
        $this->assertSame(['Karimova Madina', 'Jo‘rayev Ulug‘bek'], $students->pluck('name')->all());
        $this->assertMatchesRegularExpression('/^madina\.karimova\d{2}@brainup\.uz$/', $students[0]->email);
        $this->assertMatchesRegularExpression('/^ulugbek\.jorayev\d{2}@brainup\.uz$/', $students[1]->email);
        $this->assertSame('+998901112233', $students[0]->phone);

        $group = StudyGroup::withoutGlobalScopes()->where('name', 'Math-Olimjon-3')->firstOrFail();
        $this->assertEqualsCanonicalizing($students->pluck('id')->all(), $group->students()->pluck('users.id')->all());
    }
}

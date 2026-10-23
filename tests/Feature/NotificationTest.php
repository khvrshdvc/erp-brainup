<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Assignment;
use App\Models\Branch;
use App\Models\StudyGroup;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function setupGroup(): array
    {
        $branch = Branch::create(['name' => 'Bildirish filiali']);

        $teacher = User::factory()->create(['role' => Role::Teacher]);
        $teacher->branches()->attach($branch->id);

        $group = StudyGroup::create(['branch_id' => $branch->id, 'teacher_id' => $teacher->id, 'name' => 'Birinchi guruh']);
        $other = StudyGroup::create(['branch_id' => $branch->id, 'teacher_id' => $teacher->id, 'name' => 'Ikkinchi guruh']);

        $student = User::factory()->create(['role' => Role::Student, 'branch_id' => $branch->id]);
        $outsider = User::factory()->create(['role' => Role::Student, 'branch_id' => $branch->id]);

        $group->students()->attach($student->id);
        $other->students()->attach($outsider->id);

        return [$teacher, $student, $outsider, $group];
    }

    private function assignmentPayload(StudyGroup $group, string $action): array
    {
        return [
            'title' => 'Speaking practice',
            'study_group_id' => $group->id,
            'description' => 'Tayyorlang',
            'max_score' => 100,
            'due_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'action' => $action,
        ];
    }

    public function test_publishing_notifies_only_group_students(): void
    {
        [$teacher, $student, $outsider, $group] = $this->setupGroup();

        $this->actingAs($teacher)->post(route('teacher.assignments.store'), $this->assignmentPayload($group, 'draft'));

        $this->assertSame(0, $student->notifications()->count());

        $assignment = Assignment::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($teacher)->post(route('teacher.assignments.publish', $assignment));

        $this->assertSame(1, $student->notifications()->count());
        $this->assertSame(0, $outsider->notifications()->count());
        $this->assertSame('Yangi vazifa', $student->notifications()->first()->data['title']);
    }

    public function test_submission_and_grading_send_notifications(): void
    {
        [$teacher, $student, , $group] = $this->setupGroup();

        $this->actingAs($teacher)->post(route('teacher.assignments.store'), $this->assignmentPayload($group, 'publish'));
        $assignment = Assignment::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($student)->post(route('student.assignments.submit.store', $assignment), [
            'file' => UploadedFile::fake()->create('work.pdf', 50, 'application/pdf'),
        ]);

        $this->assertSame(1, $teacher->notifications()->count());
        $this->assertSame('Yangi topshiriq', $teacher->notifications()->first()->data['title']);

        $submission = Submission::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($teacher)->put(route('teacher.submissions.update', $submission), [
            'action' => 'grade',
            'score' => 80,
        ]);

        $titles = $student->notifications()->pluck('data')->pluck('title')->all();

        $this->assertContains('Vazifa tekshirildi', $titles);

        $this->actingAs($teacher)->put(route('teacher.submissions.update', $submission), [
            'action' => 'return',
            'feedback' => 'Qayta yozing',
        ]);

        $this->assertContains('Qayta topshirish kerak', $student->fresh()->notifications()->pluck('data')->pluck('title')->all());
    }

    public function test_notification_page_read_and_read_all(): void
    {
        [$teacher, $student, , $group] = $this->setupGroup();

        $this->actingAs($teacher)->post(route('teacher.assignments.store'), $this->assignmentPayload($group, 'publish'));

        $assignment = Assignment::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($student)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Yangi vazifa')
            ->assertSee('Speaking practice');

        $notification = $student->notifications()->firstOrFail();

        $this->actingAs($student)
            ->post(route('notifications.read', $notification->id))
            ->assertRedirect(route('student.assignments.show', $assignment, false));

        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertSame(0, $student->unreadNotifications()->count());

        $this->actingAs($teacher)->post(route('teacher.assignments.store'), $this->assignmentPayload($group, 'publish'));

        $this->assertSame(1, $student->fresh()->unreadNotifications()->count());

        $this->actingAs($student)->post(route('notifications.read-all'))->assertRedirect();

        $this->assertSame(0, $student->fresh()->unreadNotifications()->count());
    }

    public function test_cached_unread_counter_follows_new_and_read_notifications(): void
    {
        [$teacher, $student, , $group] = $this->setupGroup();

        $this->assertSame(0, $student->unreadNotificationsCount());

        $this->actingAs($teacher)->post(route('teacher.assignments.store'), $this->assignmentPayload($group, 'publish'));
        $this->assertSame(1, $student->unreadNotificationsCount());

        $notification = $student->notifications()->firstOrFail();
        $this->actingAs($student)->post(route('notifications.read', $notification->id));
        $this->assertSame(0, $student->unreadNotificationsCount());

        $this->actingAs($teacher)->post(route('teacher.assignments.store'), $this->assignmentPayload($group, 'publish'));
        $this->assertSame(1, $student->unreadNotificationsCount());

        $this->actingAs($student)->post(route('notifications.read-all'));
        $this->assertSame(0, $student->unreadNotificationsCount());
    }

    public function test_user_cannot_read_someone_elses_notification(): void
    {
        [$teacher, $student, $outsider, $group] = $this->setupGroup();

        $this->actingAs($teacher)->post(route('teacher.assignments.store'), $this->assignmentPayload($group, 'publish'));

        $notification = $student->notifications()->firstOrFail();

        $this->actingAs($outsider)->post(route('notifications.read', $notification->id))->assertNotFound();
    }

    public function test_bell_shows_unread_counter(): void
    {
        [$teacher, $student, , $group] = $this->setupGroup();

        $this->actingAs($teacher)->post(route('teacher.assignments.store'), $this->assignmentPayload($group, 'publish'));

        $this->actingAs($student)->get(route('student.dashboard'))->assertOk()->assertSee('class="count"', false);
    }

    public function test_profile_update_and_password_change(): void
    {
        [$teacher] = $this->setupGroup();

        $teacher->update(['password' => 'oldpassword1']);

        $this->actingAs($teacher)->get(route('profile.edit'))->assertOk();

        $this->actingAs($teacher)->patch(route('profile.update'), [
            'name' => 'Yangi Ism',
            'email' => $teacher->email,
            'phone' => '+998901234567',
        ])->assertRedirect();

        $this->assertSame('Yangi Ism', $teacher->fresh()->name);
        $this->assertSame('+998901234567', $teacher->fresh()->phone);

        $this->actingAs($teacher)->put(route('profile.password'), [
            'current_password' => 'noto‘g‘ri',
            'password' => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($teacher)->put(route('profile.password'), [
            'current_password' => 'oldpassword1',
            'password' => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ])->assertSessionHasNoErrors();
    }

    public function test_profile_email_must_stay_unique(): void
    {
        [$teacher, $student] = $this->setupGroup();

        $this->actingAs($teacher)->patch(route('profile.update'), [
            'name' => $teacher->name,
            'email' => $student->email,
        ])->assertSessionHasErrors('email');
    }

    public function test_students_cannot_change_their_profile_or_password(): void
    {
        [, $student] = $this->setupGroup();

        $student->update(['password' => 'oldpassword1']);

        $this->actingAs($student)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('administratoriga murojaat qiling')
            ->assertDontSee('name="current_password"', false)
            ->assertDontSee('name="email"', false);

        $this->actingAs($student)->patch(route('profile.update'), [
            'name' => 'Boshqa Ism',
            'email' => 'boshqa@example.com',
        ])->assertForbidden();

        $this->actingAs($student)->put(route('profile.password'), [
            'current_password' => 'oldpassword1',
            'password' => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ])->assertForbidden();

        $this->assertNotSame('Boshqa Ism', $student->fresh()->name);
        $this->assertTrue(Hash::check('oldpassword1', $student->fresh()->password));
    }
}

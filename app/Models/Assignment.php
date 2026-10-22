<?php

namespace App\Models;

use App\Enums\AssignmentStatus;
use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    use BelongsToBranch, HasAttachments, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'study_group_id',
        'teacher_id',
        'title',
        'description',
        'max_score',
        'due_at',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'max_score' => 'integer',
            'due_at' => 'datetime',
            'published_at' => 'datetime',
            'status' => AssignmentStatus::class,
        ];
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Whether the user currently teaches the assignment's group (a removed teacher loses access).
     */
    public function isTaughtBy(User $user): bool
    {
        return $this->studyGroup?->teacher_id === $user->id;
    }

    public function isDraft(): bool
    {
        return $this->status === AssignmentStatus::Draft;
    }

    public function isClosed(): bool
    {
        return $this->status === AssignmentStatus::Closed;
    }

    public function isOverdue(): bool
    {
        return $this->due_at !== null && $this->due_at->isPast();
    }

    public function studentState(?Submission $submission): array
    {
        if ($submission) {
            return [$submission->status->label(), $submission->status->badge()];
        }

        return $this->isOverdue() ? [__('Muddat o‘tgan'), 'err'] : [__('Topshirilmagan'), 'neutral'];
    }

    public function teacherState(int $pending): array
    {
        return match (true) {
            $this->isDraft() => [__('Qoralama'), 'neutral'],
            $this->isClosed() => [__('Yopilgan'), 'neutral'],
            $pending > 0 => [__('Tekshirilmoqda'), 'warn'],
            default => [__('E‘lon qilingan'), 'info'],
        };
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', AssignmentStatus::Published->value);
    }

    public function scopeVisibleToStudents(Builder $query): Builder
    {
        return $query->whereIn('status', [AssignmentStatus::Published->value, AssignmentStatus::Closed->value]);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('due_at')->where('due_at', '<', now());
    }
}

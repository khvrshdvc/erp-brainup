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

    public function isOverdue(): bool
    {
        return $this->due_at !== null && $this->due_at->isPast();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', AssignmentStatus::Published->value);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('due_at')->where('due_at', '<', now());
    }
}

<?php

namespace App\Models;

use App\Enums\GroupStatus;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyGroup extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'branch_id',
        'course_id',
        'teacher_id',
        'name',
        'starts_on',
        'ends_on',
        'schedule',
        'capacity',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'schedule' => 'array',
            'capacity' => 'integer',
            'status' => GroupStatus::class,
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_student', 'study_group_id', 'student_id')
            ->withPivot('joined_at');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function hasFreeSeats(): bool
    {
        return $this->students()->count() < $this->capacity;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', GroupStatus::Active->value);
    }
}

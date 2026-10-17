<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'branch_id',
        'study_group_id',
        'student_id',
        'marked_by',
        'lesson_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'lesson_date' => 'date',
            'status' => AttendanceStatus::class,
        ];
    }

    public function isAttended(): bool
    {
        return $this->status->isAttended();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function marker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function scopeAttended(Builder $query): Builder
    {
        return $query->whereIn('status', AttendanceStatus::attendedValues());
    }

    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }
}

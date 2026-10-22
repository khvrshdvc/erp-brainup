<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Submission extends Model
{
    use BelongsToBranch, HasAttachments;

    protected $fillable = [
        'branch_id',
        'assignment_id',
        'student_id',
        'body',
        'status',
        'is_late',
        'submitted_at',
        'score',
        'feedback',
        'graded_by',
        'graded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubmissionStatus::class,
            'is_late' => 'boolean',
            'submitted_at' => 'datetime',
            'graded_at' => 'datetime',
            'score' => 'integer',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function isGraded(): bool
    {
        return $this->status === SubmissionStatus::Graded;
    }

    public function isPending(): bool
    {
        return $this->status === SubmissionStatus::Submitted;
    }

    public function isReturned(): bool
    {
        return $this->status === SubmissionStatus::Returned;
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', SubmissionStatus::Submitted->value);
    }
}

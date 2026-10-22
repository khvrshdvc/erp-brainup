<?php

namespace App\Models;

use App\Services\CurrentBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    public const DEFAULT_ATTENDANCE_EDIT_DAYS = 3;

    protected $fillable = ['branch_id', 'key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public static function get(string $key, mixed $default = null, ?int $branchId = null): mixed
    {
        $branchId ??= app(CurrentBranch::class)->id();

        $setting = static::query()
            ->where('key', $key)
            ->where(fn ($query) => $query->where('branch_id', $branchId)->orWhereNull('branch_id'))
            ->orderByRaw('branch_id is null')
            ->first();

        return $setting?->value ?? $default;
    }

    /**
     * How many days back teachers may still mark or edit attendance.
     */
    public static function attendanceEditDays(): int
    {
        $days = static::get('attendance_edit_days');

        return is_numeric($days) ? max(0, (int) $days) : self::DEFAULT_ATTENDANCE_EDIT_DAYS;
    }

    public static function put(string $key, mixed $value, ?int $branchId = null): void
    {
        static::updateOrCreate(['branch_id' => $branchId, 'key' => $key], ['value' => $value]);
    }
}

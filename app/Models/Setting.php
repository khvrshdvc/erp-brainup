<?php

namespace App\Models;

use App\Services\CurrentBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public const DEFAULT_ATTENDANCE_EDIT_DAYS = 3;

    private const CACHE_TTL = 3600;

    protected $fillable = ['branch_id', 'key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    protected static function booted(): void
    {
        static::saved(fn (self $setting) => static::forgetCached($setting->key, $setting->branch_id));
        static::deleted(fn (self $setting) => static::forgetCached($setting->key, $setting->branch_id));
    }

    public static function get(string $key, mixed $default = null, ?int $branchId = null): mixed
    {
        $branchId ??= app(CurrentBranch::class)->id();

        // Settings are read on every page (sidebar) and every attendance save, so the resolved row is cached.
        // Wrapped in an array so that "no row" is cached too (the cache cannot store a bare null).
        $cached = Cache::remember(static::cacheKey($key, $branchId), self::CACHE_TTL, function () use ($key, $branchId) {
            $setting = static::query()
                ->where('key', $key)
                ->where(fn ($query) => $query->where('branch_id', $branchId)->orWhereNull('branch_id'))
                ->orderByRaw('branch_id is null')
                ->first();

            return ['value' => $setting?->value];
        });

        return $cached['value'] ?? $default;
    }

    /**
     * A branch row only affects that branch, but a global row is the fallback of every branch.
     */
    public static function forgetCached(string $key, ?int $branchId): void
    {
        $branchIds = $branchId === null ? Branch::query()->pluck('id')->all() : [$branchId];

        foreach ([null, ...$branchIds] as $id) {
            Cache::forget(static::cacheKey($key, $id));
        }
    }

    private static function cacheKey(string $key, ?int $branchId): string
    {
        return 'setting:'.($branchId ?? 'global').':'.$key;
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

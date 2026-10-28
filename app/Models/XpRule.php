<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class XpRule extends Model
{
    protected $fillable = ['code', 'title', 'points', 'is_active'];

    /** Rules added with the monthly coin rules (GamificationService::MONTHLY_RULES_FROM). */
    public const MONTHLY_RULES = [
        ['code' => 'attendance_month', 'title' => 'Oylik davomat fondi', 'points' => 100],
        ['code' => 'perfect_month', 'title' => 'Oy davomida birorta ham darsni qoldirmaslik', 'points' => 30],
    ];

    /** Per-lesson rules that only apply to lessons before the monthly rules; kept for gamification:rebuild. */
    public const PER_LESSON_CODES = ['attendance_present', 'attendance_late', 'streak_7'];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Points are read on every attendance and submission sync, so they are cached until a rule changes.
        static::saved(fn (self $rule) => static::forgetCached($rule->code));
        static::deleted(fn (self $rule) => static::forgetCached($rule->code));
    }

    public static function pointsFor(string $code): int
    {
        return (int) Cache::rememberForever(
            static::cacheKey($code),
            fn () => (int) static::query()->active()->where('code', $code)->value('points')
        );
    }

    public static function forgetCached(string $code): void
    {
        Cache::forget(static::cacheKey($code));
    }

    private static function cacheKey(string $code): string
    {
        return 'xp_rule_points:'.$code;
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereNotIn('code', self::PER_LESSON_CODES);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}

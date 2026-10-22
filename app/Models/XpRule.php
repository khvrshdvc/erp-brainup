<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class XpRule extends Model
{
    protected $fillable = ['code', 'title', 'points', 'is_active'];

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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}

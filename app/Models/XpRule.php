<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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

    public static function pointsFor(string $code): int
    {
        return (int) static::query()->active()->where('code', $code)->value('points');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}

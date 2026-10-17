<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Level extends Model
{
    protected $fillable = ['name', 'min_xp'];

    protected function casts(): array
    {
        return ['min_xp' => 'integer'];
    }

    public static function forXp(int $xp): ?self
    {
        return static::query()->where('min_xp', '<=', $xp)->orderByDesc('min_xp')->first();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

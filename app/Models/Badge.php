<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    protected $fillable = ['code', 'title', 'icon', 'description'];

    /**
     * Rarity of the badge, shown as the colour of its medal.
     */
    public function tier(): string
    {
        return match ($this->code) {
            'streak_7', 'ten_assignments' => 'silver',
            'streak_30', 'excellent' => 'gold',
            default => 'bronze',
        };
    }

    public function tierLabel(): string
    {
        return match ($this->tier()) {
            'silver' => __('Kumush'),
            'gold' => __('Oltin'),
            default => __('Bronza'),
        };
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('awarded_at');
    }
}

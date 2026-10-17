<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $fillable = ['name', 'address', 'manager_name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(User::class)->where('role', Role::Student->value);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->where('role', Role::Teacher->value);
    }

    public function studyGroups(): HasMany
    {
        return $this->hasMany(StudyGroup::class)->withoutGlobalScopes();
    }

    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}

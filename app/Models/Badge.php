<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    protected $fillable = ['code', 'title', 'icon', 'description'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('awarded_at');
    }
}

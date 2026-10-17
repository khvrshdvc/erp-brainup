<?php

namespace App\Models;

use App\Enums\XpReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XpTransaction extends Model
{
    protected $fillable = ['user_id', 'branch_id', 'points', 'reason', 'source_id'];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'reason' => XpReason::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}

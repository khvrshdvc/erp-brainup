<?php

namespace App\Models\Concerns;

use App\Models\Branch;
use App\Models\Scopes\BranchScope;
use App\Services\CurrentBranch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

trait BelongsToBranch
{
    protected static function bootBelongsToBranch(): void
    {
        static::addGlobalScope(new BranchScope);

        static::creating(function ($model) {
            if ($model->branch_id) {
                return;
            }

            $branchId = app(CurrentBranch::class)->id();

            if (! $branchId) {
                throw new RuntimeException(__('Filial tanlanmagan'));
            }

            $model->branch_id = $branchId;
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}

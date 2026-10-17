<?php

namespace App\Models\Scopes;

use App\Services\CurrentBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BranchScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $branchId = app(CurrentBranch::class)->id();

        if ($branchId !== null) {
            $builder->where($model->getTable().'.branch_id', $branchId);
        }
    }
}

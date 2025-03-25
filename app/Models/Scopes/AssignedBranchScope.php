<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class AssignedBranchScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check()) {
            $branches = Auth::user()->branches->pluck('branch_id', 'branch_id')->toArray();
            if (! empty($branches)) {
                $builder->whereIn('branch_id', $branches);
            }
        }
    }
}

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
        $table = $model->getTable();
        if (Auth::check()) {
            $branches = Auth::user()->branches->pluck('branch_id')->toArray();
            if (! empty($branches)) {
                $builder->whereIn("{$table}.branch_id", $branches);
            }
        }
    }
}

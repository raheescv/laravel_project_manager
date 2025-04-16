<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;

class CurrentBranchScope
{
    public static function apply(Builder $query)
    {
        if (session('branch_id')) {
            return $query->where('branch_id', session('branch_id'));
        }
    }
}

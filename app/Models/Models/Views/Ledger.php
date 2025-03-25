<?php

namespace App\Models\Models\Views;

use App\Models\Scopes\AssignedBranchScope;
use Illuminate\Database\Eloquent\Model;

class Ledger extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new AssignedBranchScope());
    }
}

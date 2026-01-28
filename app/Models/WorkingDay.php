<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class WorkingDay extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'day_name',
        'is_working',
        'order_no',
    ];
}

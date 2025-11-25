<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UniqueNoCounter extends Model
{
    protected $fillable = ['year', 'branch_code', 'segment', 'number'];

    public $timestamps = false;
}

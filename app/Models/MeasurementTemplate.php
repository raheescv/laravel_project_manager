<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeasurementTemplate extends Model
{
    protected $fillable = ['category_id', 'name'];

    
  
    public function category()
    {
        return $this->belongsTo(MeasurementCategory::class, 'category_id');
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeasurementValue extends Model
{
 protected $fillable = ['customer_id', 'category_id', 'measurement_template_id', 'values'];

    protected $casts = [
        'values' => 'array'
    ];

    public function template() {
        return $this->belongsTo(MeasurementTemplate::class, 'measurement_template_id');
    }

    public function category() {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function customer() {
        return $this->belongsTo(User::class, 'customer_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeasurementField extends Model
{
    protected $fillable = ['measurement_template_id','key','label','required'];

    public function template() {
        return $this->belongsTo(MeasurementTemplate::class, 'measurement_template_id');
    }
}

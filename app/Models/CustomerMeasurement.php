<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerMeasurement extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'customer_id',
        'category_id',
        'measurement_template_id',
        'value',
        'sub_category_id',
        'size',
        'width',
        'created_by',
    ];


    public function template()
    {
        return $this->belongsTo(MeasurementTemplate::class, 'measurement_template_id');
    }

    // Optional: relation to MeasurementCategory via template
    public function category()
    {
        return $this->belongsTo(MeasurementCategory::class, 'category_id');
    }
}


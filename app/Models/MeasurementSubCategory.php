<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeasurementSubCategory extends Model
{
     protected $fillable = [
        'measurement_category_id',
        'name',
    ];

    public function category()
    {
        return $this->belongsTo(
            MeasurementCategory::class,
            'measurement_category_id'
        );
    }
public static function rules($id = null)
{
    return [
        'name' => 'required|string|max:255|unique:measurement_sub_categories,name,' . $id,
        'measurement_category_id' => 'required|exists:measurement_categories,id',
    ];
}


}

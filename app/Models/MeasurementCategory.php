<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class MeasurementCategory extends Model
{
    protected $table = 'measurement_categories';

    protected $fillable = [
        'name',
        'parent_id', // add this if you want parent-child
    ];

    /**
     * Validation rules
     */
    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'name' => ['required', Rule::unique(self::class)->ignore($id)],
        ], $merge);
    }

    /**
     * Optional parent relationship
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Create parent on the fly if not exists
     */
    public static function parentCreate($parent)
    {
        $model = self::firstOrCreate(['name' => $parent]);
        return $model['id'];
    }

        public function subCategories()
    {
        return $this->hasMany(
            MeasurementSubCategory::class,
            'measurement_category_id'
        );
    }

}

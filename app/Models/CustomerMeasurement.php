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
    ];
}


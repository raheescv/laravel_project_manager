<?php

namespace App\Models;

use App\Actions\Settings\Category\CreateAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'sale_visibility_flag',
        'online_visibility_flag',
    ];

    protected $casts = [
        'sale_visibility_flag' => 'boolean',
        'online_visibility_flag' => 'boolean',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'name' => ['required', Rule::unique(self::class)->ignore($id)],
        ], $merge);
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'main_category_id');
    }

    public static function parentCreate($parent)
    {
        $model = self::firstOrCreate(['name' => $parent]);

        return $model['id'];
    }

    public function getDropDownList($request)
    {
        $self = self::orderBy('name');
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            return $query->where('name', 'like', '%'.trim($value).'%');
        });
        $self = $self->when($request['is_parent'] ?? false, function ($query, $value) {
            return $query->whereNull('parent_id');
        });
        $self = $self->when($request['parent_id'] ?? '', function ($query, $value) {
            return $query->where('parent_id', $value);
        });
        $self = $self->limit(10);
        $self = $self->get(['name', 'id'])->toArray();
        $return['items'] = $self;

        return $return;
    }

    public static function selfCreate($data)
    {
        $name = $data['name'];
        $existing = Category::firstWhere('name', $name);
        if (! $existing) {
            $response = (new CreateAction())->execute($data);
            if (! $response['success']) {
                throw new \Exception($response['message']);
            }

            return $response['data']['id'];
        } else {
            return $existing['id'];
        }
    }
}

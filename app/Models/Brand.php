<?php

namespace App\Models;

use App\Actions\Settings\Brand\CreateAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;

class Brand extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'image_path',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'name' => ['required', Rule::unique(self::class, 'name')->whereNull('deleted_at')->ignore($id)],
            'image_path' => ['nullable', 'string'],
        ], $merge);
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'brand_id');
    }

    public function getDropDownList($request)
    {
        $self = self::orderBy('name');
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            return $query->where('name', 'like', "%{$value}%");
        });
        $self = $self->limit(10);
        $self = $self->get(['name', 'id'])->toArray();
        $return['items'] = $self;

        return $return;
    }

    public static function selfCreate($name)
    {
        $existing = self::firstWhere('name', $name);
        if (! $existing) {
            $response = (new CreateAction())->execute(['name' => $name]);
            if (! $response['success']) {
                throw new \Exception($response['message']);
            }

            return $response['data']['id'];
        } else {
            return $existing['id'];
        }
    }

    /**
     * Delete the associated image file from storage
     */
    public function deleteImage()
    {
        if ($this->image_path) {
            $imagePath = storage_path('app/public/'.$this->image_path);
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class PackageItem extends Model
{
    protected $fillable = ['package_id', 'date', 'rescheduled_date', 'notes', 'status', 'created_by', 'updated_by'];

    protected $casts = [];

    public static function rules($id, $merge, $data)
    {
        return array_merge(
            [
                'package_id' => ['required', 'exists:packages,id'],
                'date' => [
                    'required',
                    'date',
                    Rule::unique('package_items')
                        ->where(function ($query) use ($data) {
                            if (isset($data['package_id'])) {
                                return $query->where('package_id', $data['package_id']);
                            }

                            return $query;
                        })
                        ->ignore($id),
                ],
                'rescheduled_date' => ['nullable', 'date'],
                'status' => ['required', Rule::in(['visited', 'rescheduled', 'pending'])],
                'notes' => ['nullable', 'string'],
            ],
            $merge,
        );
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeVisited($query)
    {
        return $query->where('status', 'visited');
    }

    public function scopeRescheduled($query)
    {
        return $query->where('status', 'rescheduled');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}

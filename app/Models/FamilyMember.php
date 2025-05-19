<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class FamilyMember extends Model
{
    protected $fillable = [
        'name',
        'gender',
        'date_of_birth',
        'father_id',
        'mother_id',
        'spouse_id',
        'image',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    // Relationships
    public function father()
    {
        return $this->belongsTo(FamilyMember::class, 'father_id');
    }

    public function mother()
    {
        return $this->belongsTo(FamilyMember::class, 'mother_id');
    }

    public function spouse()
    {
        return $this->belongsTo(FamilyMember::class, 'spouse_id');
    }

    public function children()
    {
        return $this->hasMany(FamilyMember::class, 'father_id')
            ->orWhere('mother_id', $this->id);
    }

    // Helper methods
    public function addChild(FamilyMember $child)
    {
        if ($this->gender === 'male') {
            $child->father_id = $this->id;
        } else {
            $child->mother_id = $this->id;
        }
        $child->save();
    }

    public function addSpouse(FamilyMember $spouse)
    {
        $this->spouse_id = $spouse->id;
        $spouse->spouse_id = $this->id;

        $this->save();
        $spouse->save();
    }

    public function getSiblingsAttribute()
    {
        if (! $this->father_id && ! $this->mother_id) {
            return new Collection();
        }

        return static::where(function ($query) {
            $query->where(function ($q) {
                if ($this->father_id) {
                    $q->where('father_id', $this->father_id);
                }
                if ($this->mother_id) {
                    $q->orWhere('mother_id', $this->mother_id);
                }
            })->where('id', '!=', $this->id);
        })->get();
    }

    public function getAgeAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }
}

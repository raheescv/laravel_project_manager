<?php

namespace App\Livewire;

use App\Models\FamilyMember;
use Livewire\Attributes\On;
use Livewire\Component;

class FamilyTree extends Component
{
    public $members = [];

    public $name;

    public $gender = 'male';

    public $date_of_birth;

    public $parent_id;

    public $spouse_id;

    public $relation_type;

    public $loading = true;

    protected $rules = [
        'name' => 'required|string|max:255',
        'gender' => 'required|in:male,female,other',
        'date_of_birth' => 'nullable|date',
        'parent_id' => 'nullable|exists:family_members,id',
        'spouse_id' => 'nullable|exists:family_members,id',
        'relation_type' => 'nullable|in:child,spouse',
    ];

    public function mount()
    {
        $this->loadMembers();
    }

    public function loadMembers()
    {
        $this->loading = true;

        $members = FamilyMember::with(['father', 'mother', 'spouse', 'children'])
            ->orderBy('created_at', 'asc')
            ->get();

        $this->members = $members->map(function ($member) {
            $data = [
                'id' => $member->id,
                'name' => $member->name,
                'gender' => $member->gender,
                'pids' => $member->spouse_id ? [$member->spouse_id] : [],
                'mid' => $member->mother_id,
                'fid' => $member->father_id,
                'tags' => [$member->gender],
                'father_id' => $member->father_id,
                'mother_id' => $member->mother_id,
                'spouse_id' => $member->spouse_id,
            ];

            // Add parent relationship data
            if ($member->father) {
                $data['father'] = [
                    'id' => $member->father->id,
                    'name' => $member->father->name,
                ];
            }
            if ($member->mother) {
                $data['mother'] = [
                    'id' => $member->mother->id,
                    'name' => $member->mother->name,
                ];
            }

            // Add spouse data
            if ($member->spouse) {
                $data['spouse'] = [
                    'id' => $member->spouse->id,
                    'name' => $member->spouse->name,
                    'gender' => $member->spouse->gender,
                ];
            }

            // Add birthdate if exists
            if ($member->date_of_birth) {
                $data['date_of_birth'] = $member->date_of_birth->format('Y-m-d');
            }

            // Add children data
            if ($member->children->count() > 0) {
                $data['children'] = $member->children->map(function ($child) {
                    return [
                        'id' => $child->id,
                        'name' => $child->name,
                    ];
                })->toArray();
            }

            // Set node properties for visualization
            $data['isRoot'] = ! $member->father_id && ! $member->mother_id;
            $data['level'] = $this->calculateGenerationLevel($member);

            return $data;
        })->toArray();

        $this->loading = false;
        $this->dispatch('updateFamilyTree', $this->members);
    }

    private function calculateGenerationLevel($member, $level = 0, $visited = [])
    {
        // Prevent infinite loops in case of circular relationships
        if (in_array($member->id, $visited)) {
            return $level;
        }
        $visited[] = $member->id;

        // If no parents, this is a root node
        if (! $member->father_id && ! $member->mother_id) {
            return 0;
        }

        // Get the maximum level from either parent
        $parentLevel = 0;
        if ($member->father) {
            $fatherLevel = $this->calculateGenerationLevel($member->father, $level + 1, $visited);
            $parentLevel = max($parentLevel, $fatherLevel);
        }
        if ($member->mother) {
            $motherLevel = $this->calculateGenerationLevel($member->mother, $level + 1, $visited);
            $parentLevel = max($parentLevel, $motherLevel);
        }

        return $parentLevel + 1;
    }

    #[On('refresh-tree')]
    public function refreshTree()
    {
        $this->loadMembers();
    }

    public function addMember()
    {
        $this->validate();

        try {
            $member = new FamilyMember([
                'name' => $this->name,
                'gender' => $this->gender,
                'date_of_birth' => $this->date_of_birth,
            ]);

            $member->save();

            if ($this->relation_type === 'child' && $this->parent_id) {
                $parent = FamilyMember::find($this->parent_id);
                $parent->addChild($member);
            } elseif ($this->relation_type === 'spouse' && $this->spouse_id) {
                $spouse = FamilyMember::find($this->spouse_id);
                $member->addSpouse($spouse);
            }

            $this->reset(['name', 'gender', 'date_of_birth', 'parent_id', 'spouse_id', 'relation_type']);
            $this->dispatch('success', ['message' => 'Family member added successfully']);
            $this->loadMembers();

        } catch (\Exception $e) {
            $this->dispatch('error', ['message' => 'Error adding family member: '.$e->getMessage()]);
        }
    }

    public function render()
    {
        $potentialParents = FamilyMember::orderBy('name')->get();
        $potentialSpouses = FamilyMember::whereNull('spouse_id')
            ->where('id', '!=', $this->parent_id)
            ->orderBy('name')
            ->get();

        return view('livewire.family-tree', [
            'potentialParents' => $potentialParents,
            'potentialSpouses' => $potentialSpouses,
        ]);
    }
}

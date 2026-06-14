<?php

namespace App\Livewire\Settings\ChecklistItem;

use App\Actions\Settings\ChecklistItem\CreateAction;
use App\Actions\Settings\ChecklistItem\UpdateAction;
use App\Models\Checklist;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class Page extends Component
{
    use WithFileUploads;

    protected $listeners = [
        'ChecklistItem-Page-Create-Component' => 'create',
        'ChecklistItem-Page-Update-Component' => 'edit',
    ];

    public $formData;

    public $table_id;

    public $image;

    /** Name of the currently-selected property type, to seed the TomSelect option. */
    public $propertyTypeName = '';

    public function create()
    {
        $this->mount();
        $this->dispatch('ToggleChecklistItemModal');
        $this->dispatchModalSelects();
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleChecklistItemModal');
        $this->dispatchModalSelects();
    }

    /** Seed the category + property-type TomSelect pickers to match the current form state. */
    protected function dispatchModalSelects(): void
    {
        $this->dispatch('SetChecklistModalSelects',
            category: $this->formData['category'] ?? '',
            propertyTypeId: (string) ($this->formData['property_type_id'] ?? ''),
            propertyTypeName: $this->propertyTypeName);
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        $this->image = null;
        if (! $this->table_id) {
            $this->formData = [
                'category' => '',
                'name' => '',
                'property_type_id' => '',
                'image_path' => null,
                'sort_order' => 0,
                'is_active' => true,
            ];
            $this->propertyTypeName = '';
        } else {
            $item = Checklist::with('propertyType:id,name')->find($this->table_id);
            $this->formData = $item->toArray();
            $this->propertyTypeName = $item->propertyType?->name ?? '';
        }
    }

    protected function rules()
    {
        return [
            'formData.name' => [
                'required',
                Rule::unique('checklists', 'name')
                    ->where('tenant_id', session('tenant_id'))
                    ->where('category', $this->formData['category'] ?? null)
                    ->where('property_type_id', $this->formData['property_type_id'] ?: null)
                    ->ignore($this->table_id)
                    ->whereNull('deleted_at'),
            ],
            'image' => 'nullable|image|max:2048',
        ];
    }

    protected $messages = [
        'formData.name.required' => 'The name field is required',
        'formData.name.unique' => 'The name is already registered',
        'image.image' => 'The file must be an image',
        'image.max' => 'The image size must not exceed 2MB',
    ];

    public function save($close = false)
    {
        abort_unless(auth()->user()?->can($this->table_id ? 'rent out checklist item.edit' : 'rent out checklist item.create'), 403);
        $this->validate();
        try {
            // Handle the master image upload, replacing any previous one.
            if ($this->image) {
                if ($this->table_id && ! empty($this->formData['image_path'])) {
                    Storage::disk('public')->delete($this->formData['image_path']);
                }
                $this->formData['image_path'] = $this->image->store('checklists', 'public');
            }
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->formData);
            } else {
                $response = (new UpdateAction())->execute($this->formData, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $message = $response['message'];
            $this->dispatch('success', ['message' => $message]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('ToggleChecklistItemModal');
            } else {
                $this->mount();
                $this->dispatchModalSelects();
            }
            $this->dispatch('RefreshChecklistItemTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        $categories = Checklist::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('livewire.settings.checklist-item.page', [
            'categories' => $categories,
        ]);
    }
}

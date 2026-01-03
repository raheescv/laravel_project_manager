<?php

namespace App\Livewire\Settings\Brand;

use App\Actions\Settings\Brand\CreateAction;
use App\Actions\Settings\Brand\UpdateAction;
use App\Models\Brand;
use App\Services\TenantService;
use Faker\Factory;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class Page extends Component
{
    use WithFileUploads;

    protected $listeners = [
        'Brand-Page-Create-Component' => 'create',
        'Brand-Page-Update-Component' => 'edit',
    ];

    public $brands;

    public $table_id;

    public $image;

    public function create()
    {
        $this->mount();
        $this->dispatch('ToggleBrandModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleBrandModal');
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        if (! $this->table_id) {
            $faker = Factory::create();
            $name = '';
            if (! app()->isProduction()) {
                $name = $faker->name;
            }
            $this->brands = [
                'name' => $name,
                'image_path' => null,
            ];
            $this->image = null;
        } else {
            $brand = Brand::find($this->table_id);
            $this->brands = $brand->toArray();
            $this->image = null;
        }
    }

    protected function rules()
    {
        return [
            'brands.name' => Rule::unique('brands', 'name')->where('tenant_id', session('tenant_id'))->ignore($this->table_id)->whereNull('deleted_at'),
            'image' => 'nullable|image|max:2048',
        ];
    }

    protected $messages = [
        'brands.name.required' => 'The name field is required',
        'brands.name.unique' => 'The name is already Registered',
        'image.image' => 'The file must be an image',
        'image.max' => 'The image size must not exceed 2MB',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            // Handle image upload
            if ($this->image) {
                // Delete existing image if updating and old image exists
                if ($this->table_id && isset($this->brands['image_path']) && $this->brands['image_path']) {
                    $oldImagePath = storage_path('app/public/'.$this->brands['image_path']);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                $imagePath = $this->image->store('brands', 'public');
                $this->brands['image_path'] = $imagePath;
            }
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->brands);
            } else {
                $response = (new UpdateAction())->execute($this->brands, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('ToggleBrandModal');
            } else {
                $this->mount();
            }
            $this->dispatch('RefreshBrandTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.settings.brand.page');
    }
}

<?php

namespace App\Livewire\User;

use App\Actions\User\CreateAction;
use App\Actions\User\UpdateAction;
use App\Models\User;
use App\Traits\OptimizesUploadedImage;
use Faker\Factory;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\Permission\Models\Role;

class Page extends Component
{
    use OptimizesUploadedImage;
    use WithFileUploads;

    protected $listeners = [
        'User-Page-Create-Component' => 'create',
        'User-Page-Update-Component' => 'edit',
    ];

    public $users;

    public $table_id;

    public $selectedRoles = [];

    // Newly-picked upload (Livewire temporary file) and the path of the avatar
    // already on record, kept so it can be deleted once a replacement is saved.
    public $photo;

    public $originalImage;

    public function create()
    {
        $this->mount();
        $this->dispatch('ToggleUserModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleUserModal');
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        $this->photo = null;
        if (! $this->table_id) {
            $faker = Factory::create();
            $name = '';
            $email = '';
            $mobile = '';
            $password = '';
            if (! app()->isProduction()) {
                $name = $faker->text(20);
                $email = $faker->email();
                $mobile = '+91'.rand('9000000000', '9999999999');
                $password = 'asdasd';
            }
            $this->users = [
                'name' => $name,
                'email' => $email,
                'mobile' => $mobile,
                'password' => $password,
            ];
            $this->originalImage = null;
        } else {
            $user = User::find($this->table_id);
            $this->users = $user->toArray();
            $this->selectedRoles = $user->roles->pluck('name')->toArray();
            $this->originalImage = $this->users['image'] ?? null;
        }
    }

    protected function rules()
    {
        $rules = [
            'users.name' => ['required'],
            'users.email' => ['required', 'unique:users,email,'.$this->table_id],
            'users.mobile' => ['required'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
        if (! $this->table_id) {
            $rules['users.password'] = ['required'];
        }

        return $rules;
    }

    public function removePhoto()
    {
        $this->photo = null;
        $this->users['image'] = null;
    }

    protected $messages = [
        'users.name.required' => 'The name field is required',
        'users.name.unique' => 'The name is already Registered',
        'users.code.required' => 'The code field is required',
        'users.code.unique' => 'The code is already Registered',
        'users.code.max' => 'The code field must not be greater than 20 characters.',
        'users.password.required' => 'The password field is required.',
    ];

    public function save($close = false)
    {
        abort_unless(auth()->user()?->can($this->table_id ? 'user.edit' : 'user.create'), 403);
        $this->validate();
        try {
            // A freshly-picked upload replaces the stored avatar path.
            if ($this->photo) {
                $this->users['image'] = $this->storeOptimizedImage($this->photo, 'users');
            }
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->users);
                if ($response['success'] && ! empty($this->selectedRoles)) {
                    $user = User::find($response['data']['id']);
                    $user->syncRoles($this->assignableRoles());
                }
            } else {
                $response = (new UpdateAction())->execute($this->users, $this->table_id);
                if ($response['success']) {
                    $user = User::find($this->table_id);
                    $user->syncRoles($this->assignableRoles());
                }
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            // Drop the previous file only once the new record is safely saved.
            $newImage = $response['data']['image'] ?? null;
            if ($this->originalImage && $this->originalImage !== $newImage) {
                Storage::disk('public')->delete($this->originalImage);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('ToggleUserModal');
            } else {
                $this->mount();
            }
            $this->dispatch('RefreshUserTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    /**
     * Only return roles the current user is permitted to assign.
     * The Super Admin role can be granted only by a super admin —
     * this blocks privilege escalation via the role selector.
     */
    private function assignableRoles(): array
    {
        $roles = array_filter((array) $this->selectedRoles);
        if (! auth()->user()?->is_super_admin) {
            $roles = array_filter($roles, fn ($role) => $role !== 'Super Admin');
        }

        return array_values($roles);
    }

    public function render()
    {
        $roles = Role::orderBy('name')->get();

        return view('livewire.user.page', [
            'roles' => $roles,
        ]);
    }
}

<?php

namespace App\Livewire\User\Employee;

use App\Actions\User\CreateAction;
use App\Actions\User\UpdateAction;
use App\Models\User;
use Faker\Factory;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Page extends Component
{
    protected $listeners = [
        'Employee-Page-Create-Component' => 'create',
        'Employee-Page-Update-Component' => 'edit',
    ];

    public $users;

    public $table_id;

    public $selectedRoles = [];

    public function create()
    {
        $this->mount();
        $this->dispatch('ToggleEmployeeModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleEmployeeModal');
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        if (! $this->table_id) {
            $faker = Factory::create();
            $name = '';
            $code = '';
            $email = '';
            if (! app()->isProduction()) {
                $name = $faker->name;
                $code = $faker->hexcolor;
                $email = $faker->email;
            }
            $this->users = [
                'type' => 'employee',
                'code' => $code,
                'name' => $name,
                'email' => $email,
                'password' => '',
                'designation_id' => '',
                'order_no' => '',
            ];
        } else {
            $user = User::with('designation')->find($this->table_id);
            $this->users = $user->toArray();
            $this->selectedRoles = $user->roles->pluck('name')->toArray();
        }
        $this->dispatch('SelectDropDownValues', $this->users);
    }

    protected function rules()
    {
        $rules = [
            'users.name' => ['required'],
            'users.designation_id' => ['required'],
            'users.email' => ['required', 'unique:users,email,'.$this->table_id],
            'users.max_discount_per_sale' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'users.order_no' => ['nullable', 'integer'],
        ];
        // if (! $this->table_id) {
        //     $rules['users.password'] = ['required'];
        // }

        return $rules;
    }

    protected $messages = [
        'users.name.required' => 'The name field is required',
        'users.designation_id.required' => 'The designation field is required',
        'users.name.unique' => 'The name is already Registered',
        'users.email.unique' => 'The email is already Registered',
        'users.code.required' => 'The code field is required',
        'users.code.unique' => 'The code is already Registered',
        'users.code.max' => 'The code field must not be greater than 20 characters.',
        'users.password.max' => 'The code field must not be greater than 20 characters.',
    ];

    public function save($close = false)
    {
        abort_unless(auth()->user()?->can($this->table_id ? 'employee.edit' : 'employee.create'), 403);
        $this->validate();
        try {
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
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('ToggleEmployeeModal');
            } else {
                $this->mount();
            }
            $this->dispatch('RefreshEmployeeTable');
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

        return view('livewire.user.employee.page', ['roles' => $roles]);
    }
}

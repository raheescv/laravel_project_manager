<?php

namespace App\Livewire\User;

use App\Actions\User\CreateAction;
use App\Actions\User\UpdateAction;
use App\Models\User;
use Faker\Factory;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'User-Page-Create-Component' => 'create',
        'User-Page-Update-Component' => 'edit',
    ];

    public $users;

    public $table_id;

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
        } else {
            $user = User::find($this->table_id);
            $this->users = $user->toArray();
        }
    }

    protected function rules()
    {
        $rules = [
            'users.name' => ['required'],
            'users.email' => ['required', 'unique:users,email,'.$this->table_id],
            'users.mobile' => ['required'],
        ];
        if (! $this->table_id) {
            $rules['users.password'] = ['required'];
        }

        return $rules;
    }

    protected $messages = [
        'users.name.required' => 'The name field is required',
        'users.name.unique' => 'The name is already Registered',
        'users.code.required' => 'The code field is required',
        'users.code.unique' => 'The code is already Registered',
        'users.code.max' => 'The code field must not be greater than 20 characters.',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->users);
            } else {
                $response = (new UpdateAction())->execute($this->users, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
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

    public function render()
    {
        return view('livewire.user.page');
    }
}

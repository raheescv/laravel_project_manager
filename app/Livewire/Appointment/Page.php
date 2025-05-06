<?php

namespace App\Livewire\Appointment;

use App\Actions\Appointment\CreateAction;
use App\Actions\Appointment\Item\DeleteAction as ItemDeleteAction;
use App\Actions\Appointment\UpdateAction;
use App\Models\Appointment;
use App\Models\Product;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Page extends Component
{
    public $table_id;

    public $appointments;

    public $item;

    public $items = [];

    protected $listeners = [
        'Create-Appointment-Page-Component' => 'create',
        'Edit-Appointment-Page-Component' => 'edit',
        'Update-Appointment-Page-Component' => 'update',
    ];

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        $this->items = [];
        $this->item = [
            'service_id' => null,
            'employee_id' => null,
        ];
        if ($table_id) {
            $appointment = Appointment::with(['items', 'account:id,name', 'items.service:id,name', 'items.employee:id,name'])->find($table_id);
            $this->appointments = $appointment->toArray();
            $this->appointments['account_name'] = $appointment->account?->name;
            $this->appointments['start_time'] = date('Y-m-d H:i', strtotime($appointment->start_time));
            $this->appointments['end_time'] = date('Y-m-d H:i', strtotime($appointment->end_time));
            $this->items = $appointment->items->mapWithKeys(function ($item) {
                $key = $item['service_id'].'-'.$item['employee_id'];

                return [
                    $key => [
                        'id' => $item['id'],
                        'service_id' => $item['service_id'],
                        'employee_id' => $item['employee_id'],
                        'employee' => $item['employee']['name'],
                        'service' => $item['service']['name'],
                    ],
                ];
            })->toArray();
        } else {
            $this->appointments = [
                'branch_id' => session('branch_id'),
                'account_id' => null,
                'account_name' => null,
                'start_time' => date('Y-m-d H:i'),
                'end_time' => date('Y-m-d H:i', strtotime('+1 hour')),
                'color' => '#3788d8',
                'notes' => '',
                'status' => 'pending',
            ];
        }
        $this->dispatch('SelectDropDownValues', $this->appointments);
    }

    public function create()
    {
        $this->mount();
        $this->dispatch('ToggleAppointmentBookingModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleAppointmentBookingModal');
    }

    public function update($id, $start, $end, $key, $employee_id)
    {
        $this->mount($id);
        $this->appointments['start_time'] = date('Y-m-d H:i', strtotime($start));
        $this->appointments['end_time'] = date('Y-m-d H:i', strtotime($end));
        $this->items[$key]['employee_id'] = $employee_id;
        $this->save();
    }

    public function addItem()
    {
        if (! $this->item['employee_id'] || ! $this->item['service_id']) {
            $this->dispatch('error', ['message' => 'You Need to Select Employee and Service']);

            return false;
        }

        $employee = User::find($this->item['employee_id']);
        $service = Product::find($this->item['service_id']);

        if (! $employee || ! $service) {
            $this->dispatch('error', ['message' => 'Selected employee or service not found']);

            return false;
        }
        $key = $this->item['service_id'].'-'.$this->item['employee_id'];
        $this->item['employee'] = $employee->name;
        $this->item['service'] = $service->name;
        $this->items[$key] = $this->item;
    }

    public function removeItem($key)
    {
        try {
            $id = $this->items[$key]['id'] ?? '';
            if ($id) {
                $response = (new ItemDeleteAction())->execute($id);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
            }
            unset($this->items[$key]);
            $this->dispatch('Refresh-EmployeeCalendar-Component');
            $this->dispatch('success', ['message' => 'item removed successfully']);
        } catch (\Throwable $th) {
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    protected $rules = [
        'appointments.account_id' => 'required',
        'appointments.start_time' => 'required|date',
        'appointments.end_time' => 'required|date|after:appointments.start_time',
        'appointments.notes' => 'nullable|string',
    ];

    protected $messages = [
        'appointments.account_id' => 'The customer is required.',
        'appointments.start_time.required' => 'The start time is required',
        'appointments.end_time.required' => 'The end time is required',
        'appointments.end_time.after' => 'The end time must be a date after the start time.',
        'appointments.notes.string' => 'The notes must be a string.',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            DB::beginTransaction();
            if (count($this->items) == 0) {
                throw new Exception('Please add any service', 1);
            }
            $this->appointments['items'] = $this->items;
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->appointments, Auth::id());
            } else {
                $response = (new UpdateAction())->execute($this->appointments, $this->table_id, Auth::id());
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            DB::commit();
            if (! $close) {
                $this->dispatch('CloseAppointmentBookingModal');
            }
            $this->mount($this->table_id);
            $this->dispatch('Refresh-EmployeeCalendar-Component');
        } catch (\Throwable $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.appointment.page');
    }
}

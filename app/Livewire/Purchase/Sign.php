<?php

namespace App\Livewire\Purchase;

use App\Models\Purchase;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Sign extends Component
{
    public $signature;

    public $model;

    public function mount(Purchase $model)
    {
        $this->model = $model;
    }

    public function save()
    {
        // Validate signature
        $this->validate([
            'signature' => 'required|string',
        ]);

        // Save or process the signature (e.g. save as file or in database)
        $data = $this->signature;

        // Optional: Save to storage
        $name = 'signature_'.time().'.png';
        $path = "purchase/{$this->model->id}/signatures/".$name;

        $image = str_replace('data:image/png;base64,', '', $data);
        $image = str_replace(' ', '+', $image);
        Storage::disk('public')->put($path, base64_decode($image));

        $this->model->update(['signature' => $path,'updated_by' => Auth::id()]);

        return redirect(route('purchase::print', $this->model->id));
    }

    public function render()
    {
        return view('livewire.purchase.sign');
    }
}


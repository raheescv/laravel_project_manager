<?php

namespace App\Livewire\Issue;

use App\Models\Issue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class Sign extends Component
{
    public $signature;

    public $model;

    public function mount(Issue $model)
    {
        $this->model = $model;
    }

    public function save()
    {
        $this->validate([
            'signature' => 'required|string',
        ]);

        $data = $this->signature;
        $name = 'signature_'.time().'.png';
        $path = "issue/{$this->model->id}/signatures/".$name;

        $image = str_replace('data:image/png;base64,', '', $data);
        $image = str_replace(' ', '+', $image);
        Storage::disk('public')->put($path, base64_decode($image));

        $this->model->update([
            'signature' => $path,
            'updated_by' => Auth::id(),
        ]);

        return redirect(route('issue::print', $this->model->id));
    }

    public function render()
    {
        return view('livewire.issue.sign');
    }
}

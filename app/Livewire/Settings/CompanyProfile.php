<?php

namespace App\Livewire\Settings;

use App\Models\Configuration;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Spatie\LivewireFilepond\WithFilePond;

class CompanyProfile extends Component
{
    use WithFilePond;

    public $mobile;

    public $logo;

    public $uploaded_logo;

    public function mount()
    {
        $this->uploaded_logo = Configuration::where('key', 'logo')->value('value');
        $this->mobile = Configuration::where('key', 'mobile')->value('value');
    }

    protected function rules()
    {
        $rules = [
            'logo.*' => 'mimes:jpg,jpeg,png,gif,bmp,webp,svg|max:3100',
        ];

        return $rules;
    }

    protected $messages = [
        'logo.mimetypes' => 'The logo field must be a file of type: logo.',
        'logo.*.max' => 'The logo field must not be greater than 3100 KB',
    ];

    public function save()
    {
        $this->validate();
        try {
            if ($this->logo) {
                // throw new \Exception('Please wait for the loading to complete', 1);
                $logo = url('storage/'.$this->logo->store('company_image', 'public'));
                Configuration::updateOrCreate(['key' => 'logo'], ['value' => $logo]);
                Cache::forget('logo');
            }
            Configuration::updateOrCreate(['key' => 'mobile'], ['value' => $this->mobile]);
            Cache::forget('mobile');
            $this->dispatch('success', ['message' => 'Updated Successfully']);
            $this->dispatch('filepond-reset-images');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.settings.company-profile');
    }
}

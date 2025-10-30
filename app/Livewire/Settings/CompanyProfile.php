<?php

namespace App\Livewire\Settings;

use App\Models\Configuration;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Spatie\LivewireFilepond\WithFilePond;

class CompanyProfile extends Component
{
    use WithFilePond;

    public $company_name;

    public $mobile;

    public $gst_no;

    public $logo;

    public $uploaded_logo;

    public function mount()
    {
        $this->uploaded_logo = Configuration::where('key', 'logo')->value('value');
        $this->mobile = Configuration::where('key', 'mobile')->value('value');
        $this->company_name = Configuration::where('key', 'company_name')->value('value');
        $this->gst_no = Configuration::where('key', 'gst_no')->value('value');
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
            Configuration::updateOrCreate(['key' => 'company_name'], ['value' => $this->company_name]);
            Configuration::updateOrCreate(['key' => 'gst_no'], ['value' => $this->gst_no]);
            Cache::forget('company_name');
            Cache::forget('mobile');
            Cache::forget('gst_no');
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

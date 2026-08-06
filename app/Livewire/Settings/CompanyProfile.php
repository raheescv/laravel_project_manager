<?php

namespace App\Livewire\Settings;

use App\Models\Configuration;
use App\Support\TenantCache;
use Livewire\Component;
use Spatie\LivewireFilepond\WithFilePond;

class CompanyProfile extends Component
{
    use WithFilePond;

    public $company_name;

    public $company_description;

    public $mobile;

    public $email;

    /** Public Google review link — used by the storefront's "rate us" prompt. */
    public $google_review_url;

    public $gst_no;

    public $logo;

    public $uploaded_logo;

    public function mount()
    {
        $this->uploaded_logo = Configuration::where('key', 'logo')->value('value');
        $this->mobile = Configuration::where('key', 'mobile')->value('value');
        $this->email = Configuration::where('key', 'email')->value('value');
        $this->google_review_url = Configuration::where('key', 'google_review_url')->value('value');
        $this->company_name = Configuration::where('key', 'company_name')->value('value');
        $this->company_description = Configuration::where('key', 'company_description')->value('value');
        $this->gst_no = Configuration::where('key', 'gst_no')->value('value');
    }

    protected function rules()
    {
        $rules = [
            'logo.*' => 'mimes:jpg,jpeg,png,gif,bmp,webp,svg|max:3100',
            'email' => 'nullable|email|max:255',
            // Google Maps place/review links carry long `data=!4m...` payloads, so the
            // only real ceiling is the configurations.value TEXT column.
            'google_review_url' => 'nullable|url|max:5000',
        ];

        return $rules;
    }

    protected $messages = [
        'logo.mimetypes' => 'The logo field must be a file of type: logo.',
        'logo.*.max' => 'The logo field must not be greater than 3100 KB',
        'email.email' => 'Enter a valid company email address',
        'google_review_url.url' => 'Enter the full Google review link, e.g. https://g.page/r/xxxx/review',
    ];

    public function save()
    {
        abort_unless(auth()->user()?->can('configuration.settings'), 403);
        $this->validate();
        try {
            if ($this->logo) {
                // throw new \Exception('Please wait for the loading to complete', 1);
                $logo = url('storage/'.$this->logo->store('company_image', 'public'));
                Configuration::updateOrCreate(['key' => 'logo'], ['value' => $logo]);
                TenantCache::forget('logo');
            }
            Configuration::updateOrCreate(['key' => 'mobile'], ['value' => $this->mobile]);
            Configuration::updateOrCreate(['key' => 'email'], ['value' => $this->email]);
            Configuration::updateOrCreate(['key' => 'google_review_url'], ['value' => trim((string) $this->google_review_url)]);
            Configuration::updateOrCreate(['key' => 'company_name'], ['value' => $this->company_name]);
            Configuration::updateOrCreate(['key' => 'gst_no'], ['value' => $this->gst_no]);
            Configuration::updateOrCreate(['key' => 'company_description'], ['value' => $this->company_description]);
            TenantCache::forget('company_description');
            TenantCache::forget('company_name');
            TenantCache::forget('mobile');
            TenantCache::forget('email');
            TenantCache::forget('google_review_url');
            TenantCache::forget('gst_no');
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

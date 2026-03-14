<?php

namespace App\Livewire\Settings;

use App\Models\Configuration;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;
use Livewire\WithFileUploads;

class RentOutConfiguration extends Component
{
    use WithFileUploads;

    // Bond Paper / Letterhead Mode
    public $reservation_bond_paper_mode;

    public $reservation_logo_height;

    public $reservation_footer_height;

    // Logo Uploads (temporary files)
    public $rental_reservation_logo_file;

    public $lease_reservation_logo_file;

    public $lease_residential_logo_file;

    public $rental_residential_logo_file;

    public $rent_out_agreement_footer_file;

    public $rent_out_agreement_images_files = [];

    // Existing uploaded paths (for preview)
    public $existing_rental_reservation_logo;

    public $existing_lease_reservation_logo;

    public $existing_lease_residential_logo;

    public $existing_rental_residential_logo;

    public $existing_rent_out_agreement_footer;

    public $existing_rent_out_agreement_images = [];

    public $clear_agreement_images = false;

    private array $configKeys = [
        'reservation_bond_paper_mode',
        'reservation_logo_height',
        'reservation_footer_height',
    ];

    private array $logoKeys = [
        'rental_reservation_logo_file' => 'rental_reservation_logo',
        'lease_reservation_logo_file' => 'lease_reservation_logo',
        'lease_residential_logo_file' => 'lease_residential_logo',
        'rental_residential_logo_file' => 'residential_lease_logo',
        'rent_out_agreement_footer_file' => 'rent_out_agreement_logo_footer',
    ];

    public function mount()
    {
        foreach ($this->configKeys as $key) {
            $this->{$key} = Configuration::where('key', $key)->value('value');
        }

        // Set defaults
        $this->reservation_logo_height = $this->reservation_logo_height ?: 80;
        $this->reservation_footer_height = $this->reservation_footer_height ?: 30;
        $this->reservation_bond_paper_mode = $this->reservation_bond_paper_mode ?: 'no';

        // Load existing logo paths
        $this->existing_rental_reservation_logo = Configuration::where('key', 'rental_reservation_logo')->value('value');
        $this->existing_lease_reservation_logo = Configuration::where('key', 'lease_reservation_logo')->value('value');
        $this->existing_lease_residential_logo = Configuration::where('key', 'lease_residential_logo')->value('value');
        $this->existing_rental_residential_logo = Configuration::where('key', 'residential_lease_logo')->value('value');
        $this->existing_rent_out_agreement_footer = Configuration::where('key', 'rent_out_agreement_logo_footer')->value('value');

        $imagesJson = Configuration::where('key', 'rent_out_agreement_images')->value('value');
        $this->existing_rent_out_agreement_images = $imagesJson ? (json_decode($imagesJson, true) ?: []) : [];
    }

    public function save()
    {
        $this->validate([
            'rental_reservation_logo_file' => 'nullable|image|max:2048',
            'lease_reservation_logo_file' => 'nullable|image|max:2048',
            'lease_residential_logo_file' => 'nullable|image|max:2048',
            'rental_residential_logo_file' => 'nullable|image|max:2048',
            'rent_out_agreement_footer_file' => 'nullable|image|max:2048',
            'rent_out_agreement_images_files.*' => 'nullable|image|max:2048',
        ]);

        // Save text config keys
        foreach ($this->configKeys as $key) {
            Configuration::updateOrCreate(['key' => $key], ['value' => $this->{$key}]);
        }

        // Save logo uploads
        foreach ($this->logoKeys as $property => $configKey) {
            if ($this->{$property}) {
                $path = $this->{$property}->store('rent_out_logos', 'public');
                Configuration::updateOrCreate(['key' => $configKey], ['value' => $path]);
            }
        }

        // Handle agreement images
        if ($this->clear_agreement_images) {
            Configuration::updateOrCreate(['key' => 'rent_out_agreement_images'], ['value' => json_encode([])]);
            $this->existing_rent_out_agreement_images = [];
            $this->clear_agreement_images = false;
        }

        if (! empty($this->rent_out_agreement_images_files)) {
            $images = [];
            foreach ($this->rent_out_agreement_images_files as $file) {
                $images[] = $file->store('rent_out_logos', 'public');
            }
            Configuration::updateOrCreate(['key' => 'rent_out_agreement_images'], ['value' => json_encode($images)]);
            $this->existing_rent_out_agreement_images = $images;
        }

        // Refresh existing logo previews
        $this->existing_rental_reservation_logo = Configuration::where('key', 'rental_reservation_logo')->value('value');
        $this->existing_lease_reservation_logo = Configuration::where('key', 'lease_reservation_logo')->value('value');
        $this->existing_lease_residential_logo = Configuration::where('key', 'lease_residential_logo')->value('value');
        $this->existing_rental_residential_logo = Configuration::where('key', 'residential_lease_logo')->value('value');
        $this->existing_rent_out_agreement_footer = Configuration::where('key', 'rent_out_agreement_logo_footer')->value('value');

        // Reset file inputs
        $this->reset([
            'rental_reservation_logo_file',
            'lease_reservation_logo_file',
            'lease_residential_logo_file',
            'rental_residential_logo_file',
            'rent_out_agreement_footer_file',
            'rent_out_agreement_images_files',
        ]);

        $this->dispatch('success', message: 'Rent Out configuration saved successfully.');
        Artisan::call('optimize:clear');
    }

    public function render()
    {
        return view('livewire.settings.rent-out-configuration');
    }
}

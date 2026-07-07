<?php

namespace App\Livewire\Account\Customer;

use App\Actions\Account\CreateAction;
use App\Actions\Account\UpdateAction;
use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\Country;
use App\Models\CustomerType;
use Faker\Factory;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Page extends Component
{
    use WithFileUploads;

    protected $listeners = [
        'Customer-Page-Create-Component' => 'create',
        'Customer-Page-Update-Component' => 'edit',
    ];

    public $existingCustomers = [];

    public $countries;

    public $customerTypes;

    public $accounts;

    public $parents;

    public $table_id;

    public $photo;

    public $originalImage;

    public function create($name = null, $mobile = null)
    {
        $this->mount();
        if ($name) {
            $this->accounts['name'] = $name;
        }
        if ($mobile) {
            $this->accounts['mobile'] = $mobile;
        }
        $this->getCustomerByMobile();
        $this->dispatch('ToggleCustomerModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleCustomerModal');
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        $this->photo = null;
        $this->countries = Country::pluck('name', 'name')->toArray();
        $this->customerTypes = CustomerType::pluck('name', 'id')->toArray();
        if (! $this->table_id) {
            $faker = Factory::create();
            $name = '';
            $account_type = 'asset';
            $mobile = '';
            $email = '';
            $account_category_id = AccountCategory::firstOrCreate(['name' => 'Account Receivable'])->id;
            if (! app()->isProduction()) {
                $name = $faker->name;
                $mobile = rand(90000000, 99999999);
                $email = $faker->email;
            }
            $this->accounts = [
                'account_type' => $account_type,
                'account_category_id' => $account_category_id,
                'name' => $name,
                'mobile' => $mobile,
                'whatsapp_mobile' => '',
                'email' => $email,

                'dob' => null,
                'id_no' => '',
                'nationality' => null,
                'company' => '',
                'tax_no' => '',
                'image' => null,
                'credit_period_days' => null,
                'model' => 'customer',
            ];
        } else {
            $account = Account::with('customerType:id,name')->find($this->table_id);
            $this->accounts = $account->toArray();
        }
        $this->originalImage = $this->accounts['image'] ?? null;
        $this->dispatch('SelectDropDownValues', $this->accounts);
    }

    public function updated($key, $value)
    {
        if ($key == 'accounts.mobile') {
            $this->getCustomerByMobile();
        }
    }

    public function getCustomerByMobile()
    {
        $this->existingCustomers = Account::where('mobile', $this->accounts['mobile'])->get();
    }

    protected function rules()
    {
        return [
            'accounts.name' => ['required', 'max:100'],
            'accounts.email' => ['email', 'max:50'],
            'accounts.mobile' => ['required', 'max:15'],
            'accounts.tax_no' => ['nullable', 'max:30'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }

    protected $messages = [
        'accounts.name.required' => 'The name field is required',
        'accounts.name.max' => 'The name field must not be greater than 100 characters',
        'accounts.mobile.required' => 'The mobile field is required',
        'accounts.mobile.max' => 'The name field must not be greater than 15 characters',
        'accounts.email.max' => 'The name field must not be greater than 50 characters',
        'accounts.email.email' => 'The email field must be a valid email address.',
        'accounts.tax_no.max' => 'The tax number must not be greater than 30 characters',
        'photo.image' => 'The profile photo must be an image',
        'photo.mimes' => 'The photo must be a JPG, PNG or WEBP file',
        'photo.max' => 'The photo size must not exceed 5MB',
    ];

    /**
     * Validate + persist the uploaded profile photo as soon as it is selected so
     * the user sees an inline preview without waiting for the full form save.
     */
    public function updatedPhoto()
    {
        $this->validateOnly('photo');
    }

    public function removePhoto()
    {
        // Clear the selection in-memory only; the file on disk is removed on
        // save so closing without saving leaves the stored photo intact.
        $this->photo = null;
        $this->accounts['image'] = null;
    }

    public function selectCustomer($id)
    {
        $customer = Account::find($id);
        $this->dispatch('AddToCustomerSelectBox', $customer);
        $this->dispatch('ToggleCustomerModal');
    }

    public function save($close = false)
    {
        // TODO(C7): review save authz — inline quick-add via customerSelect TomSelect (Customer-Page-Create-Component) is embedded in many unrelated forms (POS, sale-return, etc.); gating with customer.create would break legitimate quick-create. Edit path (table_id) is reachable from the standalone customer screen.
        $this->validate();
        try {
            if ($this->photo) {
                $this->accounts['image'] = $this->storeOptimizedPhoto($this->photo);
            }
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->accounts);
            } else {
                $response = (new UpdateAction())->execute($this->accounts, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            // Persisted successfully — clean up the previous file if it was
            // replaced or removed.
            $newImage = $response['data']['image'] ?? null;
            if ($this->originalImage && $this->originalImage !== $newImage) {
                Storage::disk('public')->delete($this->originalImage);
            }
            $account_type = $response['data']['account_type'];
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('ToggleCustomerModal');
            } else {
                $this->mount();
            }
            $this->accounts['account_type'] = $account_type;
            $this->dispatch('RefreshCustomerTable');
            $this->dispatch('RefreshCustomerView');
            $this->dispatch('AddToCustomerSelectBox', $response['data']);
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    /**
     * Center-crop to a square, downscale to 400px and re-encode as a compressed
     * WEBP so every customer avatar stays tiny (typically < 30KB) regardless of
     * the source upload. Falls back to storing the original file if GD is
     * unavailable for any reason.
     */
    protected function storeOptimizedPhoto($photo): string
    {
        $directory = 'customers';
        $filename = $directory.'/'.\Illuminate\Support\Str::uuid()->toString().'.webp';

        if (! function_exists('imagecreatetruecolor')) {
            return $photo->store($directory, 'public');
        }

        try {
            $source = @imagecreatefromstring(file_get_contents($photo->getRealPath()));
            if ($source === false) {
                return $photo->store($directory, 'public');
            }

            $width = imagesx($source);
            $height = imagesy($source);
            $side = min($width, $height);
            $srcX = (int) (($width - $side) / 2);
            $srcY = (int) (($height - $side) / 2);

            $size = min(400, $side);
            $canvas = imagecreatetruecolor($size, $size);
            imagecopyresampled($canvas, $source, 0, 0, $srcX, $srcY, $size, $size, $side, $side);

            ob_start();
            imagewebp($canvas, null, 82);
            $contents = ob_get_clean();

            imagedestroy($source);
            imagedestroy($canvas);

            Storage::disk('public')->put($filename, $contents);

            return $filename;
        } catch (\Throwable $e) {
            return $photo->store($directory, 'public');
        }
    }

    public function render()
    {
        return view('livewire.account.customer.page');
    }
}

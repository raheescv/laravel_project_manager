@php
    $vendorTypes = enumOptions(\App\Enums\Account\VendorType::class);
    $nationalities = ['Qatar', 'Saudi Arabia', 'United Arab Emirates', 'Kuwait', 'Bahrain', 'Oman', 'Egypt', 'Jordan', 'Lebanon', 'Syria', 'Sudan', 'India', 'Pakistan', 'Bangladesh', 'Sri Lanka', 'Nepal', 'Philippines', 'Indonesia', 'United Kingdom', 'United States', 'Other'];
    $field = function (string $key, string $label, string $icon, array $options = []) {
        return ['key' => $key, 'label' => $label, 'icon' => $icon] + $options;
    };
    $sections = [
        'basic' => ['title' => 'Basic Information', 'icon' => 'fa-info-circle text-primary', 'fields' => [
            $field('name', 'Name *', 'fa-user', ['placeholder' => 'Vendor or company name']),
            $field('alias_name', 'Name (Arabic)', 'fa-language', ['placeholder' => 'الاسم بالعربية', 'dir' => 'rtl']),
            $field('vendor_type', 'Vendor Type', 'fa-tag', ['select' => $vendorTypes, 'placeholder' => 'Select type']),
            $field('dob', 'Date Of Birth', 'fa-birthday-cake', ['type' => 'date']),
            $field('credit_period_days', 'Credit Period (days)', 'fa-clock-o', ['type' => 'number', 'placeholder' => 'e.g. 30, 60, 90']),
            $field('cr_number', 'CR No.', 'fa-file-text-o', ['placeholder' => 'Commercial registration number']),
            $field('tax_no', 'Tax No.', 'fa-file-text-o', ['placeholder' => 'Tax registration number']),
        ]],
        'contact' => ['title' => 'Contact Information', 'icon' => 'fa-phone-square text-primary', 'fields' => [
            $field('mobile', 'Mobile *', 'fa-mobile', ['type' => 'tel', 'live' => true]),
            $field('whatsapp_mobile', 'WhatsApp', 'fa-whatsapp', ['type' => 'tel', 'placeholder' => 'Same as mobile if blank']),
            $field('emergency_contact_no', 'Emergency Contact No', 'fa-phone', ['type' => 'tel']),
            $field('email', 'Email', 'fa-envelope-o', ['type' => 'email', 'placeholder' => 'name@company.com']),
            $field('po_box', 'P.O. Box', 'fa-inbox'),
            $field('contact_person', 'Contact Person', 'fa-user-plus', ['placeholder' => 'Who to call at the vendor']),
            $field('place', 'Place', 'fa-map-marker', ['placeholder' => 'Area or city']),
        ]],
        'personal' => ['title' => 'Personal Details', 'icon' => 'fa-user text-secondary', 'more' => true, 'fields' => [
            $field('id_no', 'ID Number', 'fa-credit-card'),
            $field('id_expiry_date', 'ID Expiry Date', 'fa-calendar', ['type' => 'date']),
            $field('passport_no', 'Passport No', 'fa-book'),
            $field('occupation', 'Occupation', 'fa-briefcase'),
            $field('nationality', 'Nationality', 'fa-flag', ['select' => array_combine($nationalities, $nationalities), 'placeholder' => 'Select nationality']),
            $field('company', 'Company', 'fa-building-o'),
            $field('marital_status', 'Marital Status', 'fa-users', ['select' => ['Single' => 'Single', 'Married' => 'Married', 'Divorced' => 'Divorced', 'Widowed' => 'Widowed'], 'placeholder' => 'Select marital status']),
            $field('residential_address', 'Residential Address', 'fa-home', ['textarea' => true]),
        ]],
        'business' => ['title' => 'Employment / Business Details', 'icon' => 'fa-suitcase text-info', 'more' => true, 'fields' => [
            $field('position_nature_of_business', 'Position / Nature Of Business', 'fa-suitcase'),
            $field('monthly_income', 'Monthly Income', 'fa-money', ['type' => 'number']),
            $field('employer_address', 'Employer / Business Address', 'fa-map-marker', ['textarea' => true]),
        ]],
    ];
@endphp
<div>
    <div class="modal-header py-2">
        <h1 class="modal-title fs-5"><i class="fa fa-truck text-primary me-2"></i>{{ $table_id ? 'Edit Vendor' : 'New Vendor' }}</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form wire:submit="save">
        <div class="modal-body">
            @if ($this->getErrorBag()->count())
                <div class="alert alert-danger py-2 small mb-3">
                    @foreach ($this->getErrorBag()->toArray() as $messages)
                        <div>{{ $messages[0] }}</div>
                    @endforeach
                </div>
            @endif

            @foreach ($sections as $sectionKey => $section)
                @if (! empty($section['more']) && ! $showMore)
                    @continue
                @endif
                <div class="d-flex align-items-center gap-2 fw-semibold small pb-1 mb-2 border-bottom">
                    <i class="fa {{ $section['icon'] }}"></i> {{ $section['title'] }}
                </div>
                <div class="row g-2 mb-3">
                    @foreach ($section['fields'] as $input)
                        @php($model = 'accounts.'.$input['key'])
                        <div class="{{ ! empty($input['textarea']) ? 'col-12' : 'col-12 col-sm-6 col-lg-4' }}">
                            <label class="form-label small fw-semibold mb-1" for="vendor-{{ $input['key'] }}">{{ $input['label'] }}</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="width: 32px; justify-content: center"><i class="fa {{ $input['icon'] }} text-secondary"></i></span>
                                @if (! empty($input['select']))
                                    <select id="vendor-{{ $input['key'] }}" class="form-select @error($model) is-invalid @enderror" wire:model="{{ $model }}">
                                        <option value="">{{ $input['placeholder'] ?? 'Select' }}</option>
                                        @foreach ($input['select'] as $value => $text)
                                            <option value="{{ $value }}">{{ $text }}</option>
                                        @endforeach
                                    </select>
                                @elseif (! empty($input['textarea']))
                                    <textarea id="vendor-{{ $input['key'] }}" rows="2" class="form-control @error($model) is-invalid @enderror" wire:model="{{ $model }}" placeholder="{{ $input['placeholder'] ?? '' }}"></textarea>
                                @else
                                    <input id="vendor-{{ $input['key'] }}" type="{{ $input['type'] ?? 'text' }}" class="form-control @error($model) is-invalid @enderror"
                                        @if (! empty($input['live'])) wire:model.live.debounce.400ms="{{ $model }}" @else wire:model="{{ $model }}" @endif
                                        placeholder="{{ $input['placeholder'] ?? '' }}" @if (! empty($input['dir'])) dir="{{ $input['dir'] }}" @endif
                                        @if (($input['type'] ?? '') === 'number') min="0" step="any" @endif>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                @if ($sectionKey === 'contact' && ! $showMore)
                    <button type="button" class="btn btn-light border w-100 text-start small" wire:click="$set('showMore', true)">
                        <i class="fa fa-angle-down me-1"></i> More details <span class="text-muted">· personal and business details, optional</span>
                    </button>
                @endif
            @endforeach
        </div>
        <div class="modal-footer py-2">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            @if (! $table_id)
                <button type="button" wire:click="save(1)" class="btn btn-success">Save & Add New</button>
            @endif
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>

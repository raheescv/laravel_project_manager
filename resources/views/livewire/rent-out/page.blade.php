<div>
    @php
        $status = \App\Enums\RentOut\RentOutStatus::tryFrom($rent_outs['status'] ?? '');
        $isBooking = $type === 'Booking';
        $isCancelled = ($rent_outs['status'] ?? '') === 'cancelled';
        $canDecideBooking = isset($rent_outs['id']) && $isBooking && ($rent_outs['status'] ?? '') === 'booked' && !($rent_outs['submitted_by'] ?? null);
        $recordLabel = $isBooking ? $config->bookingLabel : $config->singularLabel;
        $summary = $this->summary;
        $collectionMode = $rent_outs['collection_payment_mode'] ?? '';
        $hasTerms = collect(['remark', 'cancellation_policy_en', 'cancellation_policy_ar', 'payment_terms_en', 'payment_terms_ar', 'payment_terms_extended_en', 'payment_terms_extended_ar'])->contains(fn ($key) => filled($rent_outs[$key] ?? null));
        $currencyCode = base_currency()['code'] ?? null;
        $downShare = $summary['total'] > 0 ? min(100, round($summary['down_payment'] / $summary['total'] * 100)) : 0;
    @endphp
    <x-rent-out.form.premium />

    <form wire:submit="save" class="bkx">
        {{-- Hero --}}
        <div class="bk-card bk-hero">
            <div class="ic"><i class="fa {{ $isBooking ? 'fa-bookmark' : 'fa-file-text' }}"></i></div>
            <div style="min-width:0">
                <h1>
                    {{ $table_id ? 'Edit' : 'New' }} {{ $recordLabel }}
                    @if ($table_id)
                        <span class="no">#{{ $table_id }}</span>
                    @endif
                    @if ($status)
                        <span class="bk-chip tone-{{ $status->color() }}"><i class="fa fa-circle"></i> {{ $status->label() }}</span>
                    @endif
                </h1>
                <div class="sub">
                    <span><i class="fa fa-key"></i>{{ $summary['property_number'] ? 'Unit ' . $summary['property_number'] . ($summary['building_name'] ? ' · ' . $summary['building_name'] : '') : 'No unit selected' }}</span>
                    <span><i class="fa fa-user"></i>{{ $summary['customer_name'] ?? 'No customer selected' }}</span>
                    @if (($rent_outs['start_date'] ?? '') && ($rent_outs['end_date'] ?? ''))
                        <span><i class="fa fa-calendar"></i>{{ systemDate($rent_outs['start_date']) }} → {{ systemDate($rent_outs['end_date']) }}</span>
                    @endif
                </div>
            </div>
            @if ($table_id)
                @can($isBooking ? $config->bookingViewPermission : $config->viewPermission)
                    <div class="acts">
                        <a class="bk-btn ghost" href="{{ route($isBooking ? $config->bookingViewRoute : $config->viewRoute, $table_id) }}"><i class="fa fa-eye"></i> View</a>
                    </div>
                @endcan
            @endif
        </div>

        @if ($errors->any())
            <div class="bk-errors" role="alert">
                <i class="fa fa-exclamation-circle" style="margin-top:2px"></i>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bk-grid">
            <div style="min-width:0">

                {{-- 1 · Property & Customer --}}
                <section class="bk-card bk-sec">
                    <header>
                        <span class="n">1</span>
                        <h3>Property &amp; Customer</h3>
                        <div class="aside">
                            <label class="bk-tgl" for="vacant_only">
                                <input type="checkbox" wire:model.live="vacant_only" id="vacant_only"><span class="k"></span>Vacant only
                            </label>
                        </div>
                    </header>
                    <div class="bd">
                        <div class="bk-g">
                            <div class="span2 bk-fld">
                                <div class="bk-lbl"><span>Property / Unit<span class="req">*</span></span></div>
                                <div wire:ignore>
                                    {{ html()->select('property_id', $preFilledDropDowns['property'] ?? [])->value($rent_outs['property_id'] ?? '')->class('select-property_id')->id('property_id')->required(true)->placeholder('Search Here')->attribute('data-building-select', '#property_building_id')->attribute('data-group-select', '#property_group_id')->attribute('data-type-select', '#property_type_id') }}
                                </div>
                            </div>
                            <div class="span2 bk-fld">
                                <div class="bk-lbl">
                                    <span>Customer<span class="req">*</span></span>
                                    @if ($rent_outs['account_id'] ?? null)
                                        <a href="#" class="bk-btn link edit_customer" title="Edit Customer"><i class="fa fa-pencil"></i> Edit</a>
                                    @endif
                                </div>
                                <div wire:ignore>
                                    {{ html()->select('account_id', $preFilledDropDowns['account'] ?? [])->value($rent_outs['account_id'] ?? '')->class('select-customer_id')->id('account_id')->placeholder('Search Customer Name') }}
                                </div>
                            </div>
                            <div class="bk-fld" wire:ignore>
                                <div class="bk-lbl">Group / Project</div>
                                {{ html()->select('property_group_id', $preFilledDropDowns['group'] ?? [])->value($rent_outs['property_group_id'] ?? '')->class('select-property_group_id')->id('property_group_id')->placeholder('Select Group') }}
                            </div>
                            <div class="bk-fld" wire:ignore>
                                <div class="bk-lbl">Building</div>
                                {{ html()->select('property_building_id', $preFilledDropDowns['building'] ?? [])->value($rent_outs['property_building_id'] ?? '')->class('select-property_building_id')->id('property_building_id')->placeholder('Select Building')->attribute('data-group-select', '#property_group_id') }}
                            </div>
                            <div class="bk-fld" wire:ignore>
                                <div class="bk-lbl">Type</div>
                                {{ html()->select('property_type_id', $preFilledDropDowns['type'] ?? [])->value($rent_outs['property_type_id'] ?? '')->class('select-property_type_id')->id('property_type_id')->placeholder('Select Type') }}
                            </div>
                            <div class="bk-fld" wire:ignore>
                                <div class="bk-lbl">Salesman</div>
                                {{ html()->select('salesman_id', $preFilledDropDowns['salesman'] ?? [])->value($rent_outs['salesman_id'] ?? '')->class('select-employee_id-list')->id('salesman_id')->placeholder('Select Employee') }}
                            </div>
                        </div>
                    </div>
                </section>

                {{-- 2 · Rent / Sale details --}}
                <section class="bk-card bk-sec">
                    <header>
                        <span class="n">2</span>
                        <h3>{{ $config->detailsLabel }}</h3>
                        <div class="aside">
                            <span class="bk-chip">
                                @if ($days > 30)
                                    {{ $months }} {{ Str::plural('month', $months) }} ·
                                @endif
                                {{ $days }} {{ Str::plural('day', $days) }}
                            </span>
                        </div>
                    </header>
                    <div class="bd">
                        <div class="bk-g">
                            <div>
                                <div class="bk-lbl"><span>Start date<span class="req">*</span></span></div>
                                <input type="date" class="ctl" wire:model.live="rent_outs.start_date">
                            </div>
                            <div>
                                <div class="bk-lbl"><span>End date<span class="req">*</span></span></div>
                                <input type="date" class="ctl" wire:model.live="rent_outs.end_date">
                            </div>
                            <div>
                                <div class="bk-lbl">{{ $config->unitPriceLabel }}</div>
                                <div class="{{ $currencyCode ? 'bk-pre' : '' }}">@if ($currencyCode)<span>{{ $currencyCode }}</span>@endif<input type="number" class="ctl" wire:model.lazy="rent_outs.rent" step="0.01"></div>
                            </div>
                            <div>
                                <div class="bk-lbl">No. of terms</div>
                                <input type="number" class="ctl" wire:model.lazy="rent_outs.no_of_terms">
                            </div>
                            <div class="spanall">
                                <div class="bk-lbl">Payment frequency</div>
                                <div class="bk-pills">
                                    @foreach (['Monthly', 'Quarterly', 'Half Yearly', 'Yearly', 'One Time'] as $frequency)
                                        <button type="button" class="bk-pill {{ ($rent_outs['payment_frequency'] ?? '') === $frequency ? 'on' : '' }}"
                                            wire:click="$set('rent_outs.payment_frequency', '{{ $frequency }}')">{{ $frequency }}</button>
                                    @endforeach
                                </div>
                            </div>
                            @if ($config->isRental)
                                <div>
                                    <div class="bk-lbl"><span>Booking type<span class="req">*</span></span></div>
                                    <select class="ctl" wire:model="rent_outs.booking_type">
                                        <option value="Long Term">Long Term</option>
                                        <option value="Short Term">Short Term</option>
                                        <option value="Commercial">Commercial</option>
                                    </select>
                                </div>
                                <div class="span3">
                                    <div class="bk-lbl"><span>Included amenities <span class="bk-note">tap to toggle</span></span></div>
                                    <div class="bk-pills">
                                        @foreach (['include_electricity_water' => ['fa-bolt', 'Elec & Water'], 'include_ac' => ['fa-asterisk', 'AC'], 'include_wifi' => ['fa-wifi', 'WiFi']] as $amenityKey => [$amenityIcon, $amenityLabel])
                                            @php($isIncluded = ($rent_outs[$amenityKey] ?? 'Included') === 'Included')
                                            <button type="button" class="bk-pill {{ $isIncluded ? 'on' : 'off' }}"
                                                title="{{ $amenityLabel }}: {{ $isIncluded ? 'Included' : 'Excluded' }}"
                                                wire:click="$set('rent_outs.{{ $amenityKey }}', '{{ $isIncluded ? 'Excluded' : 'Included' }}')">
                                                <i class="fa {{ $amenityIcon }}"></i> {{ $amenityLabel }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </section>

                {{-- 3 · Down payment & collection --}}
                <section class="bk-card bk-sec">
                    <header>
                        <span class="n">3</span>
                        <h3>{{ $config->isLease ? 'Down Payment & Collection' : 'Monthly Collection' }}</h3>
                    </header>
                    <div class="bd">
                        @if ($config->isLease)
                            <div class="bk-sub">Down payment</div>
                            <div class="bk-g">
                                <div>
                                    <div class="bk-lbl">Amount</div>
                                    <div class="{{ $currencyCode ? 'bk-pre' : '' }}">@if ($currencyCode)<span>{{ $currencyCode }}</span>@endif<input type="number" class="ctl" wire:model.blur="rent_outs.down_payment" step="0.01"></div>
                                </div>
                                <div class="bk-fld" wire:ignore>
                                    <div class="bk-lbl">Payment method</div>
                                    <select id="down_payment_payment_method_id" class="select-payment_method_id-list">
                                        <option value="">Select...</option>
                                    </select>
                                </div>
                                <div class="span2">
                                    <div class="bk-lbl">Remarks</div>
                                    <input type="text" class="ctl" wire:model="rent_outs.down_payment_remarks" placeholder="Reference, cheque no., notes…">
                                </div>
                            </div>
                            <div class="bk-sub">Monthly collection</div>
                        @endif
                        <div class="bk-g">
                            <div>
                                <div class="bk-lbl">Starts on day</div>
                                <div class="bk-pre"><span>Day</span><input type="number" class="ctl" wire:model="rent_outs.collection_starting_day" min="1" max="28"></div>
                            </div>
                            <div class="span3">
                                <div class="bk-lbl"><span>Payment mode<span class="req">*</span></span></div>
                                <div class="bk-pills">
                                    @foreach (paymentModeOptions() as $modeValue => $modeLabel)
                                        <button type="button" class="bk-pill {{ $collectionMode === $modeValue ? 'on' : '' }}"
                                            wire:click="$set('rent_outs.collection_payment_mode', '{{ $modeValue }}')">
                                            <i class="fa {{ ['cash' => 'fa-money', 'cheque' => 'fa-file-text-o', 'pos' => 'fa-credit-card', 'bank_transfer' => 'fa-university'][$modeValue] ?? 'fa-circle-o' }}"></i> {{ $modeLabel }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            @if ($collectionMode && $collectionMode !== 'cash')
                                <div class="span2">
                                    <div class="bk-lbl">Bank name</div>
                                    <input type="text" class="ctl" wire:model="rent_outs.collection_bank_name" placeholder="Enter bank name">
                                </div>
                                <div class="span2">
                                    <div class="bk-lbl">Cheque starting no.</div>
                                    <input type="text" class="ctl" wire:model="rent_outs.collection_cheque_no" placeholder="Enter cheque number">
                                </div>
                            @endif
                        </div>
                    </div>
                </section>

                {{-- 4 · Remarks & terms --}}
                <section class="bk-card bk-sec is-collapsible" x-data="{ open: @js($hasTerms), lang: 'en' }" :class="{ 'closed': !open }">
                    <header @click="open = !open">
                        <span class="n">4</span>
                        <h3>Remarks &amp; Terms</h3>
                        <span class="meta">Cancellation · Payment · Extended — EN / AR</span>
                        <i class="fa fa-angle-down chev"></i>
                    </header>
                    <div class="bd" x-show="open" x-cloak>
                        <div class="bk-lbl">Remark</div>
                        <textarea class="ctl" wire:model="rent_outs.remark" rows="2" placeholder="Add any additional notes or remarks here..."></textarea>

                        <div class="bk-sub" style="margin-top:14px">Policies &amp; terms</div>
                        <div style="display:flex;justify-content:flex-end;margin:-4px 0 8px">
                            <div class="bk-seg">
                                <button type="button" :class="{ 'on': lang === 'en' }" @click="lang = 'en'">English</button>
                                <button type="button" :class="{ 'on': lang === 'ar' }" @click="lang = 'ar'">العربية</button>
                            </div>
                        </div>
                        <div class="bk-g c3" x-show="lang === 'en'">
                            <div>
                                <div class="bk-lbl">Cancellation policy</div>
                                <input type="text" class="ctl" wire:model="rent_outs.cancellation_policy_en" placeholder="Enter cancellation policy in English...">
                            </div>
                            <div>
                                <div class="bk-lbl">Payment terms</div>
                                <input type="text" class="ctl" wire:model="rent_outs.payment_terms_en" placeholder="Enter payment terms in English...">
                            </div>
                            <div>
                                <div class="bk-lbl">Payment terms — extended</div>
                                <input type="text" class="ctl" wire:model="rent_outs.payment_terms_extended_en" placeholder="Enter extended payment terms in English...">
                            </div>
                        </div>
                        <div class="bk-g c3" dir="rtl" x-show="lang === 'ar'" x-cloak>
                            <div>
                                <div class="bk-lbl">سياسة الإلغاء</div>
                                <input type="text" class="ctl" dir="rtl" wire:model="rent_outs.cancellation_policy_ar" placeholder="...أدخل قاعدة الإلغاء باللغة العربية">
                            </div>
                            <div>
                                <div class="bk-lbl">شروط الدفع</div>
                                <input type="text" class="ctl" dir="rtl" wire:model="rent_outs.payment_terms_ar" placeholder="...أدخل شروط الدفع باللغة العربية">
                            </div>
                            <div>
                                <div class="bk-lbl">شروط الدفع الممتدة</div>
                                <input type="text" class="ctl" dir="rtl" wire:model="rent_outs.payment_terms_extended_ar" placeholder="...أدخل شروط الدفع الممتدة باللغة العربية">
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            {{-- Summary rail --}}
            <aside class="bk-rail">
                <div class="bk-card bk-unit">
                    <div class="top">
                        <div class="badge-no {{ $summary['property_number'] ? '' : 'empty' }}">
                            @if ($summary['property_number'])
                                {{ $summary['property_number'] }}
                            @else
                                <i class="fa fa-key"></i>
                            @endif
                        </div>
                        <div style="min-width:0">
                            <div class="t">{{ $summary['building_name'] ?? 'Select a unit' }}</div>
                            <div class="s">{{ collect([$summary['group_name'], $summary['property_status']])->filter()->implode(' · ') ?: 'Property details appear here' }}</div>
                        </div>
                    </div>
                    <div class="bk-kv"><span>Customer</span><b class="{{ $summary['customer_name'] ? '' : 'none' }}">{{ $summary['customer_name'] ?? '—' }}</b></div>
                    <div class="bk-kv"><span>Period</span><b>{{ $days > 30 ? $months . ' mo · ' : '' }}{{ $days }} d</b></div>
                    <div class="bk-kv"><span>Salesman</span><b class="{{ $summary['salesman_name'] ? '' : 'none' }}">{{ $summary['salesman_name'] ?? '—' }}</b></div>
                </div>

                <div class="bk-card bk-money">
                    <div class="bk-eyebrow">Contract total</div>
                    <div class="big">{{ currency($summary['total']) }}<small>{{ $currencyCode }}</small></div>
                    @if ($config->isLease)
                        <div class="bk-bar"><i style="width: {{ $downShare }}%"></i></div>
                        <div class="bk-legend">
                            <span><i style="background:var(--bs-success)"></i>Down {{ $downShare }}%</span>
                            <span><i style="background:var(--acc)"></i>{{ Str::plural(Str::title($config->defaultTermLabel)) }}</span>
                        </div>
                        <div class="bk-kv"><span>Down payment</span><b>{{ currency($summary['down_payment']) }}</b></div>
                        <div class="bk-kv"><span>Balance</span><b>{{ currency($summary['balance']) }}</b></div>
                    @endif
                    <div class="bk-kv"><span>{{ $config->unitPriceLabel }} · {{ $rent_outs['payment_frequency'] ?? '' }}</span><b>{{ currency($summary['per_term']) }}</b></div>
                    <div class="bk-kv"><span>Terms</span><b>{{ (int) ($rent_outs['no_of_terms'] ?? 0) }}</b></div>
                </div>

                <div class="bk-card bk-actions">
                    @if (!$isCancelled)
                        <button type="submit" class="bk-btn ok" wire:loading.attr="disabled" wire:target="save">
                            <i class="fa fa-check" wire:loading.remove wire:target="save"></i>
                            <i class="fa fa-spinner fa-spin" wire:loading wire:target="save"></i>
                            {{ $table_id ? 'Save changes' : 'Save' }}
                        </button>
                    @endif
                    @if ($canDecideBooking)
                        <div class="row2">
                            <button type="button" wire:click="confirm" class="bk-btn pri"><i class="fa fa-check-circle"></i> Confirm</button>
                            <button type="button" wire:click="cancel" wire:confirm="Are you sure you want to cancel this booking?" class="bk-btn dng"><i class="fa fa-times-circle"></i> Cancel</button>
                        </div>
                    @endif
                    <a href="{{ $isBooking ? route($config->bookingRoute) : route($config->indexRoute) }}" class="bk-btn ghost"><i class="fa fa-arrow-left"></i> Back to list</a>
                    @if ($canDecideBooking)
                        <div class="note">Confirm turns this booking into a {{ $config->singularLabel }}</div>
                    @endif
                </div>
            </aside>
        </div>
    </form>

    @push('scripts')
        <x-select.propertyGroupSelect />
        <x-select.propertyBuildingSelect />
        <x-select.propertyTypeSelect />
        <x-select.propertySelect />
        <x-select.customerSelect />
        <x-select.employeeSelect />
        <x-select.paymentMethodSelect />

        <script type="text/javascript">
            $(document).ready(function() {
                // ── Helper: clear & reload a TomSelect by ID ──
                function clearAndReload(id) {
                    var el = document.getElementById(id);
                    if (el && el.tomselect) {
                        el.tomselect.clear();
                        el.tomselect.clearOptions();
                        el.tomselect.load('');
                    }
                }

                // ── Cascade: Group → Building → Property, Type → Property ──
                $('#property_group_id').on('change', function() {
                    @this.set('rent_outs.property_group_id', $(this).val());
                    clearAndReload('property_building_id');
                    clearAndReload('property_id');
                    @this.set('rent_outs.property_building_id', '');
                    @this.set('rent_outs.property_id', '');
                });
                $('#property_building_id').on('change', function() {
                    @this.set('rent_outs.property_building_id', $(this).val());
                    clearAndReload('property_id');
                    @this.set('rent_outs.property_id', '');
                });
                $('#property_type_id').on('change', function() {
                    @this.set('rent_outs.property_type_id', $(this).val());
                    clearAndReload('property_id');
                    @this.set('rent_outs.property_id', '');
                });
                $('#property_id').on('change', function() {
                    @this.set('rent_outs.property_id', $(this).val());
                });

                // Re-initialize property TomSelect to support vacant_only filter
                var propTs = document.querySelector('#property_id').tomselect;
                if (propTs) {
                    var origLoad = propTs.settings.load;
                    propTs.settings.load = function(query, callback) {
                        var url = "{{ route('property::property::list') }}";
                        var params = 'query=' + encodeURIComponent(query);
                        // Cascade params from data attributes
                        var buildingEl = document.querySelector('#property_building_id');
                        var buildingId = buildingEl && buildingEl.tomselect ? buildingEl.tomselect.getValue() : '';
                        if (buildingId) params += '&building_id=' + encodeURIComponent(buildingId);
                        var groupEl = document.querySelector('#property_group_id');
                        var groupId = groupEl && groupEl.tomselect ? groupEl.tomselect.getValue() : '';
                        if (groupId) params += '&property_group_id=' + encodeURIComponent(groupId);
                        var typeEl = document.querySelector('#property_type_id');
                        var typeId = typeEl && typeEl.tomselect ? typeEl.tomselect.getValue() : '';
                        if (typeId) params += '&property_type_id=' + encodeURIComponent(typeId);
                        var vacantOnly = document.querySelector('#vacant_only');
                        if (vacantOnly && vacantOnly.checked) {
                            params += '&vacant_only=1';
                        }
                        fetch(url + '?' + params).then(response => response.json()).then(json => {
                            callback(json.items);
                        }).catch(() => {
                            callback();
                        });
                    };
                }

                // Reload property list when "Vacant Only" toggles
                $('#vacant_only').on('change', function() {
                    clearAndReload('property_id');
                    @this.set('rent_outs.property_id', '');
                });

                // Customer select
                $('#account_id').on('change', function() {
                    @this.set('rent_outs.account_id', $(this).val());
                });

                // Salesman select
                $('#salesman_id').on('change', function() {
                    @this.set('rent_outs.salesman_id', $(this).val());
                });

                // Down payment payment method select
                $('#down_payment_payment_method_id').on('change', function() {
                    @this.set('rent_outs.down_payment_payment_method_id', $(this).val() || null);
                });

                // Edit customer button
                $(document).on('click', '.edit_customer', function(e) {
                    e.preventDefault();
                    var customer_id = @this.rent_outs['account_id'];
                    if (!customer_id) return;
                    Livewire.dispatch("Customer-Page-Update-Component", {
                        id: customer_id
                    });
                });

                // Auto-populate selects on edit
                Livewire.on('RentOutSelectValues', (params) => {
                    var data = params[0];
                    if (data.property_group_id) {
                        var groupTs = document.querySelector('#property_group_id').tomselect;
                        if (groupTs && data.group_name) {
                            groupTs.addOption({
                                id: data.property_group_id,
                                name: data.group_name
                            });
                            groupTs.addItem(data.property_group_id);
                        }
                    }
                    if (data.property_building_id) {
                        var buildingTs = document.querySelector('#property_building_id').tomselect;
                        if (buildingTs && data.building_name) {
                            buildingTs.addOption({
                                id: data.property_building_id,
                                name: data.building_name
                            });
                            buildingTs.addItem(data.property_building_id);
                        }
                    }
                    if (data.property_type_id) {
                        var typeTs = document.querySelector('#property_type_id').tomselect;
                        if (typeTs && data.type_name) {
                            typeTs.addOption({
                                id: data.property_type_id,
                                name: data.type_name
                            });
                            typeTs.addItem(data.property_type_id);
                        }
                    }
                    if (data.property_id) {
                        var propTs = document.querySelector('#property_id').tomselect;
                        if (propTs && data.property_name) {
                            propTs.addOption({
                                id: data.property_id,
                                name: data.property_name
                            });
                            propTs.addItem(data.property_id);
                        }
                    }
                    if (data.account_id) {
                        var custTs = document.querySelector('#account_id').tomselect;
                        if (custTs && data.customer_name) {
                            custTs.addOption({
                                id: data.account_id,
                                name: data.customer_name
                            });
                            custTs.addItem(data.account_id);
                        }
                    }
                    if (data.salesman_id) {
                        var empTs = document.querySelector('#salesman_id').tomselect;
                        if (empTs && data.salesman_name) {
                            empTs.addOption({
                                id: data.salesman_id,
                                name: data.salesman_name
                            });
                            empTs.addItem(data.salesman_id);
                        }
                    }
                    if (data.down_payment_payment_method_id) {
                        var dpTs = document.querySelector('#down_payment_payment_method_id').tomselect;
                        if (dpTs && data.down_payment_payment_method_name) {
                            dpTs.addOption({
                                id: data.down_payment_payment_method_id,
                                name: data.down_payment_payment_method_name
                            });
                            dpTs.addItem(data.down_payment_payment_method_id);
                        }
                    }
                });

                // Auto-fill group/building/type when property is selected
                Livewire.on('PropertyAutoFill', (params) => {
                    var data = params[0];
                    if (data.property_group_id) {
                        var groupTs = document.querySelector('#property_group_id').tomselect;
                        if (groupTs) {
                            groupTs.addOption({
                                id: data.property_group_id,
                                name: data.group_name
                            });
                            groupTs.setValue(data.property_group_id, true);
                        }
                    }
                    if (data.property_building_id) {
                        var buildingTs = document.querySelector('#property_building_id').tomselect;
                        if (buildingTs) {
                            buildingTs.addOption({
                                id: data.property_building_id,
                                name: data.building_name
                            });
                            buildingTs.setValue(data.property_building_id, true);
                        }
                    }
                    if (data.property_type_id) {
                        var typeTs = document.querySelector('#property_type_id').tomselect;
                        if (typeTs) {
                            typeTs.addOption({
                                id: data.property_type_id,
                                name: data.type_name
                            });
                            typeTs.setValue(data.property_type_id, true);
                        }
                    }
                });
                
                window.addEventListener('AddToCustomerSelectBox', event => {
                    var data = event.detail[0];
                    var el = document.querySelector('#account_id');
                    if (el && el.tomselect) {
                        var tomSelectInstance = el.tomselect;
                        if (data['name']) {
                            tomSelectInstance.addOption({
                                id: data['id'],
                                name: data['name'],
                                mobile: data['mobile'] || '',
                            });
                        }
                        tomSelectInstance.addItem(data['id']);
                        @this.set('rent_outs.account_id', data['id']);
                    }
                });
            });
        </script>
    @endpush
</div>

<div>
    {{-- ─────────── Header + completeness ─────────── --}}
    <div class="panel" style="margin-bottom: 12px;">
        <div class="phead">
            <span class="ic"><i class="fa fa-shield"></i></span>
            <div>
                <h4>KYC Information</h4>
                <span class="hint">Know Your Customer — {{ $completeness['filled'] }} of {{ $completeness['total'] }} fields captured</span>
            </div>
            <div class="right">
                @if ($kyc_confirmed_at)
                    <span class="tag ok"><i class="fa fa-check-circle"></i> Verified</span>
                @else
                    <span class="tag warn"><i class="fa fa-clock-o"></i> Pending</span>
                @endif
                @can('customer kyc.print')
                    @if ($account_id)
                        <a href="{{ route('account::customer::kyc', $account_id) }}" target="_blank" class="btn sm" title="Print KYC Form PDF">
                            <i class="fa fa-file-pdf-o"></i> Print KYC
                        </a>
                    @endif
                @endcan
            </div>
        </div>
        <div class="pbody" style="padding: 11px 14px;">
            <div class="meter">
                <div class="hd"><span>Completeness</span><span class="num">{{ $completeness['percent'] }}%</span></div>
                <div class="bar"><i style="width: {{ $completeness['percent'] }}%"></i></div>
                @if ($completeness['missing'] > 0)
                    <div class="note">{{ $completeness['missing'] }} {{ $completeness['missing'] === 1 ? 'field is' : 'fields are' }} still empty.</div>
                @endif
            </div>
        </div>
    </div>

    @if ($readonly)
        <div class="alert-cv info">
            <i class="fa fa-lock"></i>
            <span>You have read-only access to KYC details.</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert-cv bad">
            <i class="fa fa-exclamation-triangle"></i>
            <div>
                <b>Please fix the following:</b>
                <ul class="mb-0 ps-3 mt-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- ─────────── Identity ─────────── --}}
    <div class="panel">
        <div class="phead">
            <span class="ic"><i class="fa fa-credit-card"></i></span>
            <div><h4>Identity Details</h4></div>
        </div>
        <div class="pbody">
            <div class="row g-2">
                <div class="col-6 col-md-4 col-xl-3 fld">
                    <label>Nationality</label>
                    <select wire:model="kyc.nationality" @disabled($readonly)>
                        <option value="">Select nationality</option>
                        @foreach ($countries as $countryValue => $countryLabel)
                            <option value="{{ $countryValue }}">{{ $countryLabel }}</option>
                        @endforeach
                    </select>
                    @error('kyc.nationality')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="col-6 col-md-4 col-xl-3 fld">
                    <label>Date of Birth</label>
                    <input type="date" wire:model="kyc.dob" @disabled($readonly)>
                </div>
                <div class="col-6 col-md-4 col-xl-3 fld">
                    <label>Marital Status</label>
                    <select wire:model="kyc.marital_status" @disabled($readonly)>
                        <option value="">Select</option>
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Divorced">Divorced</option>
                        <option value="Widowed">Widowed</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl-3 fld">
                    <label>ID No</label>
                    <input type="text" wire:model="kyc.id_no" placeholder="National / QID No" @disabled($readonly)>
                    @error('kyc.id_no')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="col-6 col-md-4 col-xl-3 fld">
                    <label>ID Expiry Date</label>
                    <input type="date" wire:model.live="kyc.id_expiry_date" @disabled($readonly)>
                    @if ($expiry['id_expiry_date']['state'] !== 'none')
                        <div class="expiry {{ $expiry['id_expiry_date']['state'] }}">
                            <i class="fa {{ $expiry['id_expiry_date']['state'] === 'fine' ? 'fa-check-circle' : ($expiry['id_expiry_date']['state'] === 'soon' ? 'fa-exclamation-triangle' : 'fa-times-circle') }}"></i>
                            {{ $expiry['id_expiry_date']['label'] }}
                        </div>
                    @endif
                </div>
                <div class="col-6 col-md-4 col-xl-3 fld">
                    <label>Passport No</label>
                    <input type="text" wire:model="kyc.passport_no" placeholder="Passport No" @disabled($readonly)>
                    @error('kyc.passport_no')<span class="err">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>
    </div>

    {{-- ─────────── Contact & Address ─────────── --}}
    <div class="panel">
        <div class="phead p-ok">
            <span class="ic"><i class="fa fa-phone"></i></span>
            <div><h4>Contact &amp; Address</h4></div>
        </div>
        <div class="pbody">
            <div class="row g-2">
                <div class="col-6 col-md-4 col-xl-3 fld">
                    <label>Emergency Contact No</label>
                    <input type="text" wire:model="kyc.emergency_contact_no" @disabled($readonly)>
                    @error('kyc.emergency_contact_no')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="col-6 col-md-4 col-xl-3 fld">
                    <label>P.O. Box</label>
                    <input type="text" wire:model="kyc.po_box" @disabled($readonly)>
                </div>
                <div class="col-6 col-md-4 col-xl-3 fld">
                    <label>Contact Person</label>
                    <input type="text" wire:model="kyc.contact_person" @disabled($readonly)>
                </div>
                <div class="col-6 col-md-4 col-xl-3 fld">
                    <label>Contact Person Mobile</label>
                    <input type="text" wire:model="kyc.contact_person_mobile" @disabled($readonly)>
                </div>
                <div class="col-12 fld">
                    <label>Residential Address</label>
                    <textarea rows="2" wire:model="kyc.residential_address" placeholder="Full residential address" @disabled($readonly)></textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- ─────────── Employment / Business ─────────── --}}
    <div class="panel">
        <div class="phead p-info">
            <span class="ic"><i class="fa fa-briefcase"></i></span>
            <div><h4>Employment / Business</h4></div>
        </div>
        <div class="pbody">
            <div class="row g-2">
                <div class="col-6 col-md-4 col-xl-3 fld">
                    <label>Occupation</label>
                    <input type="text" wire:model="kyc.occupation" @disabled($readonly)>
                </div>
                <div class="col-6 col-md-4 col-xl-3 fld">
                    <label>Job Title</label>
                    <input type="text" wire:model="kyc.job" @disabled($readonly)>
                </div>
                <div class="col-6 col-md-4 col-xl-3 fld">
                    <label>Company</label>
                    <input type="text" wire:model="kyc.company" @disabled($readonly)>
                </div>
                <div class="col-6 col-md-4 col-xl-3 fld">
                    <label>Monthly Income</label>
                    <input type="number" step="0.01" wire:model="kyc.monthly_income" @disabled($readonly)>
                    @error('kyc.monthly_income')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="col-6 col-md-4 col-xl-3 fld">
                    <label>Sponsor Name</label>
                    <input type="text" wire:model="kyc.sponsor_name" @disabled($readonly)>
                </div>
                <div class="col-12 col-md-6 fld">
                    <label>Position / Nature of Business</label>
                    <input type="text" wire:model="kyc.position_nature_of_business" @disabled($readonly)>
                </div>
                <div class="col-12 fld">
                    <label>Employer Address</label>
                    <textarea rows="2" wire:model="kyc.employer_address" @disabled($readonly)></textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- ─────────── Company Documents ─────────── --}}
    <div class="panel">
        <div class="phead p-warn">
            <span class="ic"><i class="fa fa-folder-open-o"></i></span>
            <div>
                <h4>Company Documents</h4>
                <span class="hint">CR · CP · EID · Tax Card</span>
            </div>
        </div>
        <div class="pbody">
            <div class="row g-2">
                <div class="col-6 col-md-4 col-xl-3 fld">
                    <label>CR Number</label>
                    <input type="text" wire:model="kyc.cr_number" @disabled($readonly)>
                </div>
                <div class="col-6 col-md-4 col-xl-3 fld">
                    <label>CR Issue Date</label>
                    <input type="date" wire:model="kyc.cr_issue_date" @disabled($readonly)>
                </div>
                <div class="col-6 col-md-4 col-xl-3 fld">
                    <label>CR Expiry Date</label>
                    <input type="date" wire:model.live="kyc.cr_expiry_date" @disabled($readonly)>
                    @if ($expiry['cr_expiry_date']['state'] !== 'none')
                        <div class="expiry {{ $expiry['cr_expiry_date']['state'] }}">
                            <i class="fa {{ $expiry['cr_expiry_date']['state'] === 'fine' ? 'fa-check-circle' : ($expiry['cr_expiry_date']['state'] === 'soon' ? 'fa-exclamation-triangle' : 'fa-times-circle') }}"></i>
                            {{ $expiry['cr_expiry_date']['label'] }}
                        </div>
                    @endif
                </div>
                <div class="col-6 col-md-4 col-xl-3 fld">
                    <label>CP Number</label>
                    <input type="text" wire:model="kyc.cp_number" @disabled($readonly)>
                </div>
                <div class="col-6 col-md-4 col-xl-3 fld">
                    <label>CP Issue Date</label>
                    <input type="date" wire:model="kyc.cp_issue_date" @disabled($readonly)>
                </div>
                <div class="col-6 col-md-4 col-xl-3 fld">
                    <label>CP Expiry Date</label>
                    <input type="date" wire:model.live="kyc.cp_expiry_date" @disabled($readonly)>
                    @if ($expiry['cp_expiry_date']['state'] !== 'none')
                        <div class="expiry {{ $expiry['cp_expiry_date']['state'] }}">
                            <i class="fa {{ $expiry['cp_expiry_date']['state'] === 'fine' ? 'fa-check-circle' : ($expiry['cp_expiry_date']['state'] === 'soon' ? 'fa-exclamation-triangle' : 'fa-times-circle') }}"></i>
                            {{ $expiry['cp_expiry_date']['label'] }}
                        </div>
                    @endif
                </div>
                <div class="col-6 col-md-4 col-xl-3 fld">
                    <label>EID Number</label>
                    <input type="text" wire:model="kyc.eid_number" @disabled($readonly)>
                </div>
                <div class="col-6 col-md-4 col-xl-3 fld">
                    <label>EID Issue Date</label>
                    <input type="date" wire:model="kyc.eid_issue_date" @disabled($readonly)>
                </div>
                <div class="col-6 col-md-4 col-xl-3 fld">
                    <label>EID Expiry Date</label>
                    <input type="date" wire:model.live="kyc.eid_expiry_date" @disabled($readonly)>
                    @if ($expiry['eid_expiry_date']['state'] !== 'none')
                        <div class="expiry {{ $expiry['eid_expiry_date']['state'] }}">
                            <i class="fa {{ $expiry['eid_expiry_date']['state'] === 'fine' ? 'fa-check-circle' : ($expiry['eid_expiry_date']['state'] === 'soon' ? 'fa-exclamation-triangle' : 'fa-times-circle') }}"></i>
                            {{ $expiry['eid_expiry_date']['label'] }}
                        </div>
                    @endif
                </div>
                <div class="col-6 col-md-4 col-xl-3 fld">
                    <label>Tax Card No</label>
                    <input type="text" wire:model="kyc.tax_card_no" @disabled($readonly)>
                </div>
                <div class="col-6 col-md-4 col-xl-3 fld">
                    <label>Tax Card Issue Date</label>
                    <input type="date" wire:model="kyc.tax_card_issue_date" @disabled($readonly)>
                </div>
            </div>
        </div>
    </div>

    @can('customer kyc.edit')
        <div class="savebar">
            <span class="note"><i class="fa fa-info-circle"></i> Saved directly to the customer record.</span>
            <button type="button" class="btn" wire:click="resetKyc" wire:loading.attr="disabled" wire:target="resetKyc">
                <i class="fa fa-refresh"></i> Reset
            </button>
            <button type="button" class="btn pri" wire:click="saveKyc" wire:loading.attr="disabled" wire:target="saveKyc">
                <span wire:loading.remove wire:target="saveKyc"><i class="fa fa-floppy-o"></i></span>
                <span wire:loading wire:target="saveKyc"><i class="fa fa-circle-o-notch fa-spin"></i></span>
                Save KYC Details
            </button>
        </div>
    @endcan

</div>

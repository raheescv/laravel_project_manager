@php
    // Read-only spec sheet — rebuilt with STOCK BOOTSTRAP 5.3 only (no .cvx
    // custom classes). Colours come from --bs-primary and Bootstrap's own
    // subtle/emphasis tokens, so it follows the theme + light/dark by itself.
    $tel = ['Mobile', 'WhatsApp', 'Emergency Contact', 'Person Mobile'];

    $identity = [
        ['Name', $account?->name],
        ['Customer Type', $account?->customerType?->name],
        ['Date of Birth', ! empty($account?->dob) ? systemDate($account->dob) : null],
        ['ID No', $account?->id_no],
        ['Passport No', $account?->passport_no],
        ['Nationality', $account?->nationality],
        ['Marital Status', $account?->marital_status],
    ];

    $contact = [
        ['Mobile', $account?->mobile],
        ['WhatsApp', $account?->whatsapp_mobile],
        ['Emergency Contact', $account?->emergency_contact_no],
        ['Email', $account?->email],
        ['P.O. Box', $account?->po_box],
        ['Contact Person', $account?->contact_person],
        ['Person Mobile', $account?->contact_person_mobile],
    ];

    $company = [
        ['Company', $account?->company],
        ['Tax No', $account?->tax_no],
        ['Occupation', $account?->occupation],
        [
            'Credit Period',
            ! empty($account?->credit_period_days)
                ? $account->credit_period_days . ' ' . ($account->credit_period_days == 1 ? 'Day' : 'Days')
                : null,
        ],
        ['Registered On', $account?->created_at ? systemDate($account->created_at) : null],
    ];

    // HasDocumentExpiryState class → Bootstrap badge colour
    $badgeMap = ['ok' => 'text-bg-success', 'warn' => 'text-bg-warning', 'bad' => 'text-bg-danger', 'mute' => 'text-bg-secondary'];
@endphp

<div>
    <div class="row g-3">
        {{-- ══════════════════════════  DETAILS  ══════════════════════════ --}}
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                <div class="card-header bg-body-tertiary border-bottom d-flex align-items-center gap-2 py-3">
                    <span class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary-emphasis rounded-3 p-2 lh-1">
                        <i class="fa fa-user fa-fw"></i>
                    </span>
                    <div class="me-auto">
                        <div class="fw-bold text-body-emphasis lh-1">Customer Details</div>
                        <div class="text-secondary small mt-1">Read only — edit from the Edit button or the KYC tab</div>
                    </div>
                    @if ($account?->kyc_confirmed_at)
                        <span class="badge rounded-pill text-bg-success"><i class="fa fa-check"></i> Verified</span>
                    @else
                        <span class="badge rounded-pill text-bg-warning"><i class="fa fa-clock-o"></i> Unverified</span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        {{-- Identity --}}
                        <div class="col-12 col-md-6">
                            <div class="small fw-bold text-uppercase text-body-secondary mb-2">
                                <i class="fa fa-user fa-fw text-primary me-1"></i> Identity
                            </div>
                            <dl class="row g-0 mb-0">
                                @foreach ($identity as [$label, $value])
                                    <dt class="col-5 text-secondary fw-normal py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">{{ $label }}</dt>
                                    <dd class="col-7 text-end fw-semibold py-2 mb-0 text-break {{ ! $loop->last ? 'border-bottom' : '' }} {{ filled($value) ? 'text-body-emphasis' : 'text-secondary' }}">
                                        {{ filled($value) ? $value : '—' }}
                                    </dd>
                                @endforeach
                            </dl>
                        </div>

                        {{-- Contact --}}
                        <div class="col-12 col-md-6">
                            <div class="small fw-bold text-uppercase text-body-secondary mb-2">
                                <i class="fa fa-phone fa-fw text-primary me-1"></i> Contact
                            </div>
                            <dl class="row g-0 mb-0">
                                @foreach ($contact as [$label, $value])
                                    <dt class="col-5 text-secondary fw-normal py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">{{ $label }}</dt>
                                    <dd class="col-7 text-end fw-semibold py-2 mb-0 text-break {{ ! $loop->last ? 'border-bottom' : '' }} {{ filled($value) ? 'text-body-emphasis' : 'text-secondary' }}">
                                        @if (! filled($value))
                                            &mdash;
                                        @elseif ($label === 'Email')
                                            <a href="mailto:{{ $value }}" class="link-primary text-decoration-none">{{ $value }}</a>
                                        @elseif (in_array($label, $tel, true))
                                            <a href="tel:{{ $value }}" class="link-primary text-decoration-none">{{ $value }}</a>
                                        @else
                                            {{ $value }}
                                        @endif
                                    </dd>
                                @endforeach
                            </dl>
                        </div>
                    </div>

                    <hr class="my-3 opacity-25">

                    <div class="row g-4">
                        {{-- Company & Terms --}}
                        <div class="col-12 col-md-6">
                            <div class="small fw-bold text-uppercase text-body-secondary mb-2">
                                <i class="fa fa-briefcase fa-fw text-primary me-1"></i> Company &amp; Terms
                            </div>
                            <dl class="row g-0 mb-0">
                                @foreach ($company as [$label, $value])
                                    <dt class="col-5 text-secondary fw-normal py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">{{ $label }}</dt>
                                    <dd class="col-7 text-end fw-semibold py-2 mb-0 text-break {{ ! $loop->last ? 'border-bottom' : '' }} {{ filled($value) ? 'text-body-emphasis' : 'text-secondary' }}">
                                        {{ filled($value) ? $value : '—' }}
                                    </dd>
                                @endforeach
                            </dl>
                        </div>

                        {{-- Address --}}
                        <div class="col-12 col-md-6">
                            <div class="small fw-bold text-uppercase text-body-secondary mb-2">
                                <i class="fa fa-map-marker fa-fw text-primary me-1"></i> Address
                            </div>
                            <div class="mb-3">
                                <div class="text-secondary mb-1">Residential Address</div>
                                <div class="{{ filled($account?->residential_address) ? 'fw-semibold text-body-emphasis' : 'text-secondary' }}">{{ $account?->residential_address ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="text-secondary mb-1">Employer Address</div>
                                <div class="{{ filled($account?->employer_address) ? 'fw-semibold text-body-emphasis' : 'text-secondary' }}">{{ $account?->employer_address ?: '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════  SIDE RAIL  ══════════════════════════ --}}
        <div class="col-12 col-lg-4">
            {{-- Verification --}}
            <div class="card border-0 shadow-sm rounded-4 mb-3 overflow-hidden">
                <div class="card-header bg-body-tertiary border-bottom d-flex align-items-center gap-2 py-3">
                    <span class="d-inline-flex align-items-center justify-content-center bg-success-subtle text-success-emphasis rounded-3 p-2 lh-1">
                        <i class="fa fa-check-circle-o fa-fw"></i>
                    </span>
                    <div class="fw-bold text-body-emphasis">Verification</div>
                </div>
                <div class="card-body">
                    @if (! empty($account?->kyc_confirmed_at))
                        <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                            <span class="badge rounded-pill text-bg-success"><i class="fa fa-check"></i> Confirmed</span>
                            <span class="small text-secondary">{{ systemDate($account->kyc_confirmed_at) }}</span>
                        </div>
                        @if (! empty($account->kycConfirmer?->name))
                            <p class="text-secondary small mb-3">by <b class="text-body-emphasis">{{ $account->kycConfirmer->name }}</b></p>
                        @else
                            <div class="mb-3"></div>
                        @endif
                    @else
                        <span class="badge rounded-pill text-bg-warning"><i class="fa fa-clock-o"></i> Not Confirmed</span>
                        <p class="text-secondary small mt-2 mb-3">Nobody has verified this customer's details yet.</p>
                    @endif

                    @can('customer kyc.confirm')
                        <button type="button" class="btn btn-success w-100" wire:click="confirmBasicDetails"
                            wire:confirm="Confirm that the customer's basic details are verified and correct?"
                            wire:loading.attr="disabled" wire:target="confirmBasicDetails">
                            <span wire:loading.remove wire:target="confirmBasicDetails"><i class="fa fa-check me-1"></i></span>
                            <span wire:loading wire:target="confirmBasicDetails"><i class="fa fa-circle-o-notch fa-spin me-1"></i></span>
                            {{ ! empty($account?->kyc_confirmed_at) ? 'Re-confirm' : 'Confirm Details' }}
                        </button>
                    @endcan
                </div>
            </div>

            {{-- Activity --}}
            <div class="card border-0 shadow-sm rounded-4 mb-3 overflow-hidden">
                <div class="card-header bg-body-tertiary border-bottom d-flex align-items-center gap-2 py-3">
                    <span class="d-inline-flex align-items-center justify-content-center bg-info-subtle text-info-emphasis rounded-3 p-2 lh-1">
                        <i class="fa fa-line-chart fa-fw"></i>
                    </span>
                    <div class="me-auto fw-bold text-body-emphasis">Activity</div>
                    <span class="small text-secondary">All time</span>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent">
                        <span class="text-secondary"><i class="fa fa-shopping-cart fa-fw me-1"></i> Invoices</span>
                        <span class="fw-bold text-body-emphasis">{{ $snapshot['count'] }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent">
                        <span class="text-secondary"><i class="fa fa-money fa-fw me-1"></i> Avg. Invoice</span>
                        <span class="fw-bold text-body-emphasis">{{ currency($snapshot['average']) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent">
                        <span class="text-secondary"><i class="fa fa-clock-o fa-fw me-1"></i> Last Purchase</span>
                        <span class="fw-bold {{ $snapshot['last_date'] ? 'text-body-emphasis' : 'text-secondary' }}">{{ $snapshot['last_date'] ? systemDate($snapshot['last_date']) : '—' }}</span>
                    </li>
                </ul>
            </div>

            {{-- Document expiry --}}
            @can('customer kyc.view')
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-body-tertiary border-bottom d-flex align-items-center gap-2 py-3">
                        <span class="d-inline-flex align-items-center justify-content-center bg-warning-subtle text-warning-emphasis rounded-3 p-2 lh-1">
                            <i class="fa fa-bell-o fa-fw"></i>
                        </span>
                        <div class="fw-bold text-body-emphasis">Document Expiry</div>
                    </div>
                    <ul class="list-group list-group-flush">
                        @foreach ($documents as $label => $state)
                            <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent">
                                <span class="text-secondary">{{ $label }}</span>
                                <span class="badge rounded-pill {{ $badgeMap[$state['class']] ?? 'text-bg-secondary' }}">{{ $state['label'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endcan
        </div>
    </div>
</div>

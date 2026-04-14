<x-app-layout>
    @php
        $parentCategory = $account->accountCategory?->parent;
        $categoryPath = $parentCategory
            ? $parentCategory->name . ' / ' . $account->accountCategory->name
            : ($account->accountCategory->name ?? null);

        $natureMap = [
            'asset' => ['label' => 'Debit Balance', 'color' => 'danger', 'icon' => 'pli-arrow-up-2'],
            'expense' => ['label' => 'Debit Balance', 'color' => 'danger', 'icon' => 'pli-arrow-up-2'],
            'liability' => ['label' => 'Credit Balance', 'color' => 'success', 'icon' => 'pli-arrow-down-2'],
            'income' => ['label' => 'Credit Balance', 'color' => 'success', 'icon' => 'pli-arrow-down-2'],
            'equity' => ['label' => 'Credit Balance', 'color' => 'success', 'icon' => 'pli-arrow-down-2'],
        ];
        $nature = $natureMap[$account->account_type] ?? ['label' => 'Unknown', 'color' => 'secondary', 'icon' => 'pli-question'];

        $typeColorMap = [
            'asset' => 'primary',
            'liability' => 'warning',
            'income' => 'success',
            'expense' => 'danger',
            'equity' => 'info',
        ];
        $typeColor = $typeColorMap[$account->account_type] ?? 'secondary';

        $hasContactInfo = $account->mobile || $account->whatsapp_mobile || $account->email || $account->place;
        $hasBusinessInfo = $account->company || $account->credit_period_days || $account->id_no || $account->nationality || $account->dob || $account->customerType;
        $hasOpeningBalance = ($account->opening_debit && $account->opening_debit > 0) || ($account->opening_credit && $account->opening_credit > 0);
        $hasExtra = $hasContactInfo || $hasBusinessInfo || $hasOpeningBalance || $account->description || $account->second_reference_no;
    @endphp

    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item">Accounts</li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $account->name }}</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-1">{{ $account->name }}</h1>
            @if ($account->alias_name)
                <p class="lead text-muted mb-0">{{ $account->alias_name }}</p>
            @endif
        </div>
    </div>

    <div class="content__boxed">
        <div class="content__wrap">

            {{-- Account Profile Card --}}
            <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                {{-- Top colored accent bar --}}
                <div class="bg-{{ $typeColor }}" style="height: 4px;"></div>

                <div class="card-body px-4 py-3">
                    {{-- Identity Grid --}}
                    <div class="row g-0">
                        {{-- Account Type --}}
                        <div class="col-lg col-md-4 col-sm-6 py-3 px-3" style="border-right: 1px solid rgba(0,0,0,.06);">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-3 d-flex align-items-center justify-content-center bg-{{ $typeColor }} bg-opacity-10"
                                    style="width: 44px; height: 44px; min-width: 44px;">
                                    <i class="pli-financial fs-4 text-{{ $typeColor }}"></i>
                                </div>
                                <div>
                                    <div class="text-muted" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em;">Account Type</div>
                                    <div class="fw-bold" style="font-size: 0.95rem;">
                                        {{ $account->account_type ? ucfirst($account->account_type) : 'Not Set' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Nature --}}
                        <div class="col-lg col-md-4 col-sm-6 py-3 px-3" style="border-right: 1px solid rgba(0,0,0,.06);">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-3 d-flex align-items-center justify-content-center bg-{{ $nature['color'] }} bg-opacity-10"
                                    style="width: 44px; height: 44px; min-width: 44px;">
                                    <i class="{{ $nature['icon'] }} fs-4 text-{{ $nature['color'] }}"></i>
                                </div>
                                <div>
                                    <div class="text-muted" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em;">Nature</div>
                                    <div class="fw-bold" style="font-size: 0.95rem;">{{ $nature['label'] }}</div>
                                </div>
                            </div>
                        </div>
                        {{-- Category Head --}}
                        <div class="col-lg col-md-6 col-sm-6 py-3 px-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-3 d-flex align-items-center justify-content-center bg-secondary bg-opacity-10"
                                    style="width: 44px; height: 44px; min-width: 44px;">
                                    <i class="pli-folder fs-4 text-secondary"></i>
                                </div>
                                <div>
                                    <div class="text-muted" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em;">Category Head</div>
                                    <div class="fw-bold" style="font-size: 0.95rem;">
                                        @if ($categoryPath)
                                            @if ($parentCategory)
                                                <span class="text-muted fw-normal">{{ $parentCategory->name }} /</span>
                                                {{ $account->accountCategory->name }}
                                            @else
                                                {{ $categoryPath }}
                                            @endif
                                        @else
                                            <span class="text-muted">Uncategorized</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Extended Details --}}
                    @if ($hasExtra)
                        <div style="border-top: 1px solid rgba(0,0,0,.06); margin: 0 -1.5rem; padding: 1rem 1.5rem 0;">
                            <div class="row g-4">
                                {{-- Contact --}}
                                @if ($hasContactInfo)
                                    <div class="col-lg-3 col-md-6">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <i class="pli-smartphone-4 text-muted"></i>
                                            <span style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em;" class="text-muted fw-bold">Contact</span>
                                        </div>
                                        @if ($account->mobile)
                                            <div class="d-flex align-items-center gap-2 mb-1 ps-1">
                                                <a href="tel:{{ $account->mobile }}" class="text-decoration-none small">{{ $account->mobile }}</a>
                                            </div>
                                        @endif
                                        @if ($account->whatsapp_mobile && $account->whatsapp_mobile !== $account->mobile)
                                            <div class="d-flex align-items-center gap-2 mb-1 ps-1">
                                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $account->whatsapp_mobile) }}" target="_blank" class="text-decoration-none small text-success">
                                                    <i class="fa fa-whatsapp me-1"></i>{{ $account->whatsapp_mobile }}
                                                </a>
                                            </div>
                                        @endif
                                        @if ($account->email)
                                            <div class="d-flex align-items-center gap-2 mb-1 ps-1">
                                                <a href="mailto:{{ $account->email }}" class="text-decoration-none small">{{ $account->email }}</a>
                                            </div>
                                        @endif
                                        @if ($account->place)
                                            <div class="small text-muted ps-1">{{ $account->place }}</div>
                                        @endif
                                    </div>
                                @endif

                                {{-- Business Details --}}
                                @if ($hasBusinessInfo)
                                    <div class="col-lg-3 col-md-6">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <i class="pli-id-card text-muted"></i>
                                            <span style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em;" class="text-muted fw-bold">Details</span>
                                        </div>
                                        <div class="ps-1">
                                            @if ($account->company)
                                                <div class="small mb-1"><span class="text-muted">Company:</span> {{ $account->company }}</div>
                                            @endif
                                            @if ($account->customerType)
                                                <div class="small mb-1"><span class="text-muted">Type:</span> {{ $account->customerType->name }}</div>
                                            @endif
                                            @if ($account->id_no)
                                                <div class="small mb-1"><span class="text-muted">ID:</span> {{ $account->id_no }}</div>
                                            @endif
                                            @if ($account->nationality)
                                                <div class="small mb-1"><span class="text-muted">Nationality:</span> {{ $account->nationality }}</div>
                                            @endif
                                            @if ($account->dob)
                                                <div class="small mb-1"><span class="text-muted">DOB:</span> {{ \Carbon\Carbon::parse($account->dob)->format('d M Y') }}</div>
                                            @endif
                                            @if ($account->credit_period_days)
                                                <div class="small mb-1"><span class="text-muted">Credit:</span> {{ $account->credit_period_days }} days</div>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                {{-- Financial --}}
                                @if ($hasOpeningBalance || $account->second_reference_no)
                                    <div class="col-lg-3 col-md-6">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <i class="pli-coins text-muted"></i>
                                            <span style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em;" class="text-muted fw-bold">Opening Balance</span>
                                        </div>
                                        <div class="ps-1">
                                            @if ($account->opening_debit && $account->opening_debit > 0)
                                                <div class="d-flex justify-content-between small mb-1">
                                                    <span class="text-muted">Debit</span>
                                                    <span class="fw-medium text-danger">{{ currency($account->opening_debit) }}</span>
                                                </div>
                                            @endif
                                            @if ($account->opening_credit && $account->opening_credit > 0)
                                                <div class="d-flex justify-content-between small mb-1">
                                                    <span class="text-muted">Credit</span>
                                                    <span class="fw-medium text-success">{{ currency($account->opening_credit) }}</span>
                                                </div>
                                            @endif
                                            @if ($account->second_reference_no)
                                                <div class="d-flex justify-content-between small mb-1">
                                                    <span class="text-muted">Ref #</span>
                                                    <span class="fw-medium">{{ $account->second_reference_no }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                {{-- Description --}}
                                @if ($account->description)
                                    <div class="col-lg-3 col-md-6">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <i class="pli-file-edit text-muted"></i>
                                            <span style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em;" class="text-muted fw-bold">Description</span>
                                        </div>
                                        <p class="small text-muted mb-0 ps-1">{{ $account->description }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Ledger --}}
            @livewire('account.view', ['account_id' => $id])
        </div>
    </div>

    @push('scripts')
    @endpush
</x-app-layout>

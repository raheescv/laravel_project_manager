<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item" aria-current="page">Report</li>
                    <li class="breadcrumb-item active" aria-current="page">Vendor Aging</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Vendor Aging Report</h1>
            <p class="lead">
                Report showing outstanding vendor invoices with aging analysis
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card">
                <div class="card-header bg-white">
                    <div class="col-12">
                        <div class="bg-light rounded-3 border shadow-sm">
                            <div class="p-3">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="form-label text-muted fw-semibold small mb-2" for="from_date">
                                                <i class="demo-psi-calendar-4 me-1"></i> From Date
                                            </label>
                                            {{ html()->date('from_date')->value(date('Y-m-01'))->class('form-control form-control-sm vendor_aging_table_change')->id('from_date') }}
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="form-label text-muted fw-semibold small mb-2" for="to_date">
                                                <i class="demo-psi-calendar-4 me-1"></i> To Date
                                            </label>
                                            {{ html()->date('to_date')->value(date('Y-m-d'))->class('form-control form-control-sm vendor_aging_table_change')->id('to_date') }}
                                        </div>
                                    </div>
                                    <div class="col-md-3" wire:ignore>
                                        <div class="form-group">
                                            <label class="form-label text-muted fw-semibold small mb-2" for="branch_id">
                                                <i class="demo-psi-home me-1"></i> Branch
                                            </label>
                                            {{ html()->select('branch_id', [])->value('')->class('select-branch_id-list vendor_aging_table_change')->id('table_branch_id')->placeholder('All Branches') }}
                                        </div>
                                    </div>
                                    <div class="col-md-3" wire:ignore>
                                        <div class="form-group">
                                            <label class="form-label text-muted fw-semibold small mb-2" for="vendor_id">
                                                <i class="demo-psi-building me-1"></i> Vendor
                                            </label>
                                            {{ html()->select('vendor_id', [])->value('')->class('select-vendor_id-list vendor_aging_table_change')->id('vendor_id')->placeholder('All Vendors') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @livewire('report.vendor.vendor-aging')
            </div>
        </div>
    </div>
    @push('scripts')
        <x-select.vendorSelect />
        <x-select.branchSelect />
    @endpush
</x-app-layout>


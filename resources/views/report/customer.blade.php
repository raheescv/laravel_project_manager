<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item" aria-current="page">Report</li>
                    <li class="breadcrumb-item active" aria-current="page">Customer</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Customer Report</h1>
            <p class="lead">
                Report For Customer
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card">
                <div class="card-header">
                    <div class="card-tools">
                        <div class="row g-2 mb-3">
                            <div class="col-md-3">
                                <b><label for="from_date">From Date</label></b>
                                {{ html()->date('from_date')->value(date('Y-m-01'))->class('form-control table_change customer_item_table_change')->id('from_date') }}
                            </div>
                            <div class="col-md-3">
                                <b><label for="to_date">To Date</label></b>
                                {{ html()->date('to_date')->value(date('Y-m-d'))->class('form-control table_change customer_item_table_change')->id('to_date') }}
                            </div>
                            <div class="col-md-3" wire:ignore>
                                <b><label for="customer_id">Customer</label></b>
                                {{ html()->select('customer_id', [])->value('')->class('select-customer_id-list table_change customer_item_table_change')->id('customer_id')->placeholder('All') }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#visit-history">
                                Visit Summary
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#customer-items">
                                Items Summary
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#customer-sales">
                                Sales
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="visit-history">
                            @livewire('report.customer.customer-visit-history')
                        </div>
                        <div class="tab-pane fade" id="customer-items">
                            @livewire('report.customer.customer-items')
                        </div>
                        <div class="tab-pane fade" id="customer-sales">
                            @livewire('report.customer.customer-sales')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <x-select.customerSelect />
        <x-select.productSelect />
    @endpush
</x-app-layout>

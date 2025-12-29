<x-app-layout>
    @push('styles')
        <style>
            .content-section {
                padding: 1.5rem;
                background: white;
                border-radius: 0.5rem;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                margin-bottom: 1.5rem;
            }

            .status-badge {
                padding: 6px 16px;
                border-radius: 20px;
                font-size: 0.875rem;
                font-weight: 500;
                display: inline-block;
            }

            .status-in_progress {
                background-color: #fef3c7;
                color: #d97706;
            }

            .status-completed {
                background-color: #d1fae5;
                color: #059669;
            }

            .status-cancelled {
                background-color: #fee2e2;
                color: #dc2626;
            }

            .balance-display {
                font-size: 1.5rem;
                font-weight: bold;
                color: #667eea;
            }

            .section-title {
                border-bottom: 2px solid #667eea;
                padding-bottom: 0.5rem;
                margin-bottom: 1.5rem;
            }

            /* Package Details Styling */
            .form-group-enhanced {
                position: relative;
                margin-bottom: 1.5rem;
            }

            .form-group-enhanced label {
                display: flex;
                align-items: center;
                margin-bottom: 0.5rem;
                font-weight: 600;
                color: #374151;
                font-size: 0.9rem;
            }

            .form-group-enhanced label i {
                margin-right: 0.5rem;
                color: #667eea;
                font-size: 1rem;
            }

            .form-group-enhanced .form-control,
            .form-group-enhanced select {
                border: 1.5px solid #e5e7eb;
                border-radius: 0.5rem;
                padding: 0.75rem 1rem;
                transition: all 0.3s ease;
                background-color: #fff;
            }

            .form-group-enhanced .form-control:focus,
            .form-group-enhanced select:focus {
                border-color: #667eea;
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
                outline: none;
            }

            .form-group-enhanced .form-control:hover,
            .form-group-enhanced select:hover {
                border-color: #9ca3af;
            }

            .summary-card {
                background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                border: 2px solid #e2e8f0;
                border-radius: 0.75rem;
                padding: 1.5rem;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            }

            .summary-card table {
                margin-bottom: 0;
            }

            .summary-card table th {
                background-color: transparent;
                border: none;
                padding: 0.75rem 1rem;
                font-weight: 600;
                color: #475569;
                font-size: 0.9rem;
            }

            .summary-card table td {
                border: none;
                padding: 0.75rem 1rem;
                text-align: right;
                font-weight: 700;
                font-size: 1.1rem;
                color: #1e293b;
            }

            .summary-card table tr:first-child th {
                color: #667eea;
            }

            .summary-card table tr:first-child td {
                color: #667eea;
                font-size: 1.3rem;
            }

            .summary-card table tr:last-child th {
                color: #059669;
            }

            .summary-card table tr:last-child td {
                color: #059669;
                font-size: 1.2rem;
            }

            .form-section-divider {
                height: 1px;
                background: linear-gradient(to right, transparent, #e5e7eb, transparent);
                margin: 2rem 0;
            }

            .input-icon-wrapper {
                position: relative;
            }

            .input-icon-wrapper i {
                position: absolute;
                left: 1rem;
                top: 50%;
                transform: translateY(-50%);
                color: #9ca3af;
                z-index: 10;
            }

            .input-icon-wrapper .form-control {
                padding-left: 2.5rem;
            }

            .textarea-wrapper {
                position: relative;
            }

            .textarea-wrapper textarea {
                min-height: 100px;
                resize: vertical;
            }
        </style>
    @endpush

    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('package::index') }}">Package</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $id ? 'Edit' : 'Create' }}</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">{{ $id ? 'Edit Package' : 'Create Package' }}</h1>
            <p class="lead">
                {{ $id ? 'Update package details, items, and payments' : 'Create a new package with items and payments' }}
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('package.page', ['table_id' => $id])
            <!-- Package Items Section -->
            @if ($id)
                <div class="row">
                    <div class="col-7">
                        <div class="content-section">
                            <h4 class="section-title">
                                <i class="demo-psi-calendar-4 me-2"></i>Package Terms
                            </h4>
                            @livewire('package.items', ['package_id' => $id])
                        </div>
                    </div>

                    <!-- Payments Section -->
                    <div class="col-5">
                        <div class="content-section">
                            <h4 class="section-title">
                                <i class="demo-psi-wallet me-2"></i>Payments
                            </h4>
                            @livewire('package.payments', ['package_id' => $id])
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    @push('scripts')
        @include('components.select.accountSelect')
    @endpush
</x-app-layout>

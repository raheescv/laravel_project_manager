<div class="combo-offerselection-container">
    <div class="modal-header bg-gradient-primary text-white py-4">
        <h5 class="modal-title d-flex align-items-center m-0">
            <i class="demo-psi-shopping-bag me-2 fs-4"></i>
            Combo Selection
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>

    <div class="modal-body p-4">
        <!-- ComboOffer Selection Cards -->
        <div class="combo-offercards mb-4">
            <div class="row g-4">
                <div class="col-md-9">
                    <div wire:ignore>
                        <label for="combo_offer_id">Combo</label>
                        {{ html()->select('combo_offer_id', [])->value('')->class('select-combo_offer_id-list')->id('combo_offer_id')->placeholder('Select ComboOffer') }}
                    </div>
                </div>
                <div class="col-md-3"> <br>
                    <button type="button" class="btn btn-primary" wire:click="add">
                        <i class="demo-psi-shopping-cart me-1"></i>
                        Add Combo
                        @if (count($selectedComboOffers) > 0)
                            <span class="badge bg-white text-primary ms-2">{{ count($selectedComboOffers) }}</span>
                        @endif
                    </button>
                </div>
            </div>
        </div>

        @if ($selectedComboOfferId)
            <!-- Service Selection -->
            <div class="service-selection-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold m-0">
                        <i class="demo-psi-gear me-2"></i>
                        Available Services
                    </h6>
                    <span class="badge bg-primary">
                        {{ count($selectedServices) }} Selected
                    </span>
                </div>

                @if ($filtered_combo_offer_items && count($filtered_combo_offer_items) > 0)
                    <div class="row g-2">
                        @foreach ($filtered_combo_offer_items as $key => $item)
                            <div class="col-md-6 col-lg-4">
                                <label class="w-100 mb-0" for="service-{{ $key }}">
                                    <div class="card service-card h-100 {{ in_array($key, $selectedServices) ? 'border-primary bg-light' : 'border-light' }}">
                                        <div class="card-body p-2">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-grow-1">
                                                    <input type="checkbox" class="form-check-input me-2" value="{{ $key }}" wire:model.live="selectedServices"
                                                        id="service-{{ $key }}">
                                                    <span class="text-truncate">{{ $item['employee_name'] }} - {{ $item['name'] }}</span>
                                                </div>
                                                <div class="text-end ms-2">
                                                    <small class="text-success d-block">
                                                        {{ currency($item['unit_price']) }}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-info d-flex align-items-center">
                        <i class="demo-psi-information me-2 fs-5"></i>
                        <span>No services available for this combo offer</span>
                    </div>
                @endif
            </div>
        @endif

        @if (count($selectedComboOffers) > 0)
            <div class="selected-combo-offer-summary mt-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="summary-header d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <div class="summary-icon me-2">
                                    <i class="demo-psi-shopping-cart"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0">Combo Offer Summary</h6>
                                    <small class="text-muted">Review your selected combo offer and services</small>
                                </div>
                            </div>
                        </div>
                        <div class="selected-combo-offer">
                            <div class="combo-offer-grid">
                                @foreach ($selectedComboOffers as $index => $combo_offer)
                                    @php
                                        $originalTotal = collect($combo_offer['items'])->sum('unit_price');
                                        $discountPercent = round((1 - $combo_offer['amount'] / $originalTotal) * 100, 1);
                                    @endphp
                                    <div class="combo-offer-summary-item">
                                        <div class="card combo-offer-summary-card h-100">
                                            <div class="card-header py-2 px-2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="combo-offer-indicator"></div>
                                                        <h6 class="combo-offer-name mb-0">{{ $combo_offer['combo_offer_name'] }}</h6>
                                                    </div>
                                                    <button type="button" class="btn-close btn-close-sm" wire:click="remove({{ $index }})"></button>
                                                </div>
                                            </div>
                                            <div class="card-body p-2 d-flex flex-column">
                                                <div class="combo-offer-quick-stats rounded-3 mb-3">
                                                    <div class="d-flex justify-content-around gap-2">
                                                        <div class="stat-item">
                                                            <div class="stat-info">
                                                                <div class="stat-value fw-bold">{{ count($combo_offer['items']) }}</div>
                                                                <div class="stat-label">Services</div>
                                                            </div>
                                                        </div>
                                                        <div class="stat-item">
                                                            <div class="stat-info">
                                                                <div class="stat-value fw-bold text-success">{{ $discountPercent }}%</div>
                                                                <div class="stat-label">Savings</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="combo-offer-services flex-grow-1">
                                                    <div class="table-responsive h-100">
                                                        <table class="table table-sm service-price-table mb-0">
                                                            <tbody>
                                                                @foreach ($combo_offer['items'] as $item)
                                                                    <tr>
                                                                        <td class="py-1 w-60">
                                                                            <span class="service-name">{{ $item['employee_name'] }} - {{ $item['name'] }}</span>
                                                                        </td>
                                                                        <td class="text-end py-2 w-40">
                                                                            <div class="d-flex align-items-center justify-content-end gap-2">
                                                                                <span class="text-muted text-decoration-line-through" style="font-size: 0.8rem;">
                                                                                    {{ currency($item['unit_price']) }}
                                                                                </span>
                                                                                <span class="badge bg-danger-subtle text-danger rounded-pill" data-bs-toggle="tooltip"
                                                                                    title="You Save {{ currency($item['unit_price'] - $item['combo_offer_price']) }}">
                                                                                    -{{ currency($item['unit_price'] - $item['combo_offer_price']) }}
                                                                                </span>
                                                                                <span class="text-success fw-bold">
                                                                                    {{ currency($item['combo_offer_price']) }}
                                                                                </span>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="combo-offer-footer mt-2">
                                                    <div class="total-row d-flex justify-content-between align-items-center py-2 px-1">
                                                        <span class="fw-medium">ComboOffer Total</span>
                                                        <span class="fw-bold">{{ number_format($combo_offer['amount'], 2) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-primary" wire:click="save">
            Submit
        </button>
    </div>
    <style>
        .combo-offer-quick-stats {
            background: linear-gradient(to right, rgba(var(--bs-primary-rgb), 0.05), rgba(var(--bs-success-rgb), 0.05));
            padding: 1rem;
            border: 1px solid rgba(var(--bs-primary-rgb), 0.1);
        }

        .stat-icon-wrapper {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }

        .stat-info {
            text-align: center;
        }

        .stat-value {
            font-size: 1.25rem;
            line-height: 1.2;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--bs-gray-600);
        }

        .icon-circle {
            background: linear-gradient(135deg, var(--bs-primary), #2979ff);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .combo-offer-summary-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 16px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(var(--bs-primary-rgb), 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .combo-offer-summary-card:hover {
            transform: translateY(-4px);
        }

        .combo-offer-header {
            background: linear-gradient(135deg, var(--bs-primary), #2979ff);
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .combo-offer-title {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .btn-remove {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease;
        }

        .btn-remove:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .combo-offer-content {
            padding: 1.5rem;
        }

        .combo-offer-stats {
            display: flex;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }

        .stat-item {
            flex: 1;
            text-align: center;
            padding: 0.5rem;
            background: rgba(var(--bs-primary-rgb), 0.05);
            border-radius: 12px;
        }

        .stat-label {
            color: var(--bs-gray-600);
            font-size: 0.875rem;
        }

        .stat-value {
            font-weight: 600;
            font-size: 1.1rem;
            margin-top: 0.25rem;
        }

        .service-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .service-price {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .original-price {
            color: var(--bs-gray-500);
            text-decoration: line-through;
            font-size: 0.875rem;
        }

        .discount-price {
            color: var(--bs-success);
            font-weight: 600;
        }

        .combo-offer-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid rgba(var(--bs-primary-rgb), 0.1);
            font-weight: 600;
        }

        .total-price {
            font-size: 1.2rem;
            color: var(--bs-primary);
        }

        .combo-offer-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.08);
        }

        .combo-offer-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.12);
        }

        .combo-offer-card.active {
            background: linear-gradient(to bottom, rgba(var(--bs-primary-rgb), 0.05), transparent);
        }

        .combo-offer-banner {
            height: 80px;
            background: linear-gradient(135deg, var(--bs-primary) 0%, #2979ff 100%);
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }

        .combo-offer-card:hover .combo-offer-banner {
            opacity: 1;
        }

        .combo-offer-icon-wrapper {
            width: 64px;
            height: 64px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: 48px;
            left: 50%;
            transform: translateX(-50%);
            box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.08);
        }

        .combo-offer-icon-wrapper i {
            font-size: 28px;
        }

        .combo-offer-stats .badge {
            border-radius: 8px;
            font-weight: 500;
        }

        .combo-offer-selected-indicator {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 32px;
            height: 32px;
            background: var(--bs-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            animation: scaleIn 0.3s ease;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .service-card {
            transition: all 0.2s ease;
            cursor: pointer;
            border-radius: 8px;
        }

        .service-card:hover {
            border-color: var(--bs-primary) !important;
            background-color: var(--bs-light);
            transform: translateY(-2px);
        }

        .service-card .form-check-input {
            pointer-events: none;
        }

        label {
            cursor: pointer;
            margin: 0;
        }

        .combo-offer-summary-card {
            border-left: 4px solid var(--bs-primary);
            border-radius: 12px;
        }

        /* ComboOffer Summary Styling */
        .selected-combo-offer-summary {
            --summary-spacing: 0.75rem;
        }

        .summary-icon {
            width: 32px;
            height: 32px;
            background: rgba(var(--primary-rgb), 0.1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--bs-primary);
            font-size: 1rem;
        }

        .combo-offer-summary-card {
            border: 1px solid var(--bs-border-color);
            border-radius: 8px;
        }

        .combo-offer-summary-card .card-header {
            background: var(--bs-light);
            border-bottom: 1px solid var(--bs-border-color);
        }

        .combo-offer-indicator {
            width: 3px;
            height: 16px;
            background: var(--bs-primary);
            border-radius: 2px;
        }

        .combo-offer-name {
            font-size: 0.9375rem;
            font-weight: 500;
        }

        .btn-close-sm {
            font-size: 0.75rem;
            padding: 0.25rem;
        }

        .combo-offer-quick-stats {
            border-bottom: 1px solid var(--bs-border-color);
            padding-bottom: var(--summary-spacing);
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            gap: 0.125rem;
        }

        .stat-value {
            font-size: 0.9375rem;
            font-weight: 500;
        }

        .service-price-table {
            font-size: 0.875rem;
            margin: 0;
        }

        .service-price-table td {
            border: none;
            vertical-align: middle;
        }

        .service-name {
            font-size: 0.875rem;
            color: var(--bs-gray-700);
        }

        .total-row {
            border-top: 1px solid var(--bs-border-color);
            background: rgba(var(--primary-rgb), 0.02);
        }

        .total-row td {
            color: var(--bs-gray-800);
        }

        @media (max-width: 767.98px) {
            .combo-offer-quick-stats {
                flex-wrap: wrap;
            }

            .service-name {
                font-size: 0.8125rem;
            }
        }

        /* ComboOffer Summary Grid Layout */
        .combo-offer-grid {
            display: grid;
            gap: 1rem;
            padding: 0.5rem;
        }

        /* Single combo offer takes full width */
        .combo-offer-grid:has(.combo-offer-summary-item:only-child) {
            grid-template-columns: 1fr;
        }

        /* Two or more combo-offer show 2 per row */
        .combo-offer-grid:not(:has(.combo-offer-summary-item:only-child)) {
            grid-template-columns: repeat(2, 1fr);
        }

        @media (max-width: 767.98px) {
            .combo-offer-grid {
                grid-template-columns: 1fr !important;
                /* Force single column on mobile regardless of item count */
            }
        }

        .combo-offer-summary-item {
            min-width: 0;
            /* Ensures content doesn't overflow */
        }

        .combo-offer-summary-card {
            height: 100%;
            display: flex;
            flex-direction: column;
            border: 1px solid var(--bs-border-color);
            border-radius: 8px;
        }

        .combo-offer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }

        .combo-offer-summary-card {
            display: flex;
            flex-direction: column;
            height: 100%;
            border: 1px solid var(--bs-border-color);
            border-radius: 8px;
        }

        .combo-offer-summary-card .card-body {
            display: flex;
            flex-direction: column;
            height: 100%;
            padding: 0.75rem;
        }

        .combo-offer-services {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
        }

        .service-price-table {
            margin-bottom: 0;
        }

        .service-price-table td {
            padding: 0.4rem 0.75rem;
            vertical-align: middle;
        }

        .w-60 {
            width: 60%;
        }

        .w-40 {
            width: 40%;
        }

        .combo-offer-quick-stats {
            padding: 0.5rem;
            background: rgba(var(--primary-rgb), 0.03);
            border-radius: 6px;
            margin: 0 -0.25rem 0.75rem;
        }

        .stat-item {
            padding: 0.5rem;
            flex: 1;
        }

        .total-row {
            margin: 0 -0.25rem -0.25rem;
            padding: 0.75rem;
            background: rgba(var(--primary-rgb), 0.03);
            border-radius: 0 0 8px 8px;
        }

        @media (max-width: 767.98px) {
            .combo-offer-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#combo_offer_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('selectedComboOfferId', value);
                });

                window.addEventListener('OpenComboOfferBox', event => {
                    @this.set('selectedComboOfferId', null);
                    document.querySelector('#combo_offer_id').tomselect.clear();
                    document.querySelector('#combo_offer_id').tomselect.clearOptions();
                    document.querySelector('#combo_offer_id').tomselect.open();
                });
            });
        </script>
    @endpush
</div>

@php
    $yesNo = fn (string $key, string $label, ?string $hint = null): array => ['key' => $key, 'label' => $label, 'hint' => $hint, 'on' => $this->{$key} === 'yes'];
@endphp

<div class="scx">
    @include('livewire.settings.partials.panel-styles')

    <form wire:submit="save" class="d-flex flex-column gap-3">
        {{-- 1 · Checkout defaults --}}
        <section class="scx-section">
            <div class="scx-head">
                <span class="scx-ic" style="--tone:#2f6fd6"><i class="fa fa-shopping-cart"></i></span>
                <div>
                    <h6>Checkout Defaults</h6>
                    <p>What a new sale starts with.</p>
                </div>
            </div>
            <div class="scx-body">
                <div class="row g-3">
                    <div class="col-md-6 col-xl-4">
                        <label class="form-label" for="sale_type">Sale Type</label>
                        {{ html()->select('sale_type', saleTypes())->value('')->class('form-select')->placeholder('Select Sale Type')->attribute('wire:model', 'sale_type') }}
                    </div>
                    <div class="col-md-6 col-xl-4">
                        <label class="form-label" for="default_status">Default Status</label>
                        {{ html()->select('default_status', saleStatuses())->value('')->class('form-select')->placeholder('Select Default Status')->attribute('wire:model', 'default_status') }}
                    </div>
                    <div class="col-md-6 col-xl-4">
                        <label class="form-label" for="default_product_type">Default Product Type</label>
                        {{ html()->select('default_product_type', ['product' => 'Products', 'service' => 'Services', '' => 'All Types'])->value('')->class('form-select')->placeholder('Select Default Product Type')->attribute('wire:model', 'default_product_type') }}
                    </div>
                    <div class="col-md-6 col-xl-4">
                        <label class="form-label" for="default_quantity">Default Quantity</label>
                        {{ html()->input('number', 'default_quantity')->value('')->class('form-control')->attribute('step', '0.001')->placeholder('e.g. 1 or 0.001')->attribute('wire:model', 'default_quantity') }}
                    </div>
                    <div class="col-md-6 col-xl-8">
                        <label class="form-label" for="sale_item_row_mode">Same Product Cart Rows</label>
                        {{ html()->select('sale_item_row_mode', ['merge' => 'Single Row (merge quantity)', 'separate' => 'Multiple Rows (add separately)'])->value('')->class('form-select')->placeholder('Choose how repeated product clicks behave')->attribute('wire:model', 'sale_item_row_mode') }}
                        <div class="form-text">Tapping the same product again adds to its row, or starts a new one.</div>
                    </div>
                </div>
                <div class="scx-toggles mt-3">
                    @include('livewire.settings.partials.sale-toggle', $yesNo('default_customer_enabled', 'Default Customer', 'Start every sale with the General Customer selected.'))
                </div>
            </div>
        </section>

        {{-- 2 · Checkout rules --}}
        <section class="scx-section">
            <div class="scx-head">
                <span class="scx-ic" style="--tone:#d94848"><i class="fa fa-shield"></i></span>
                <div>
                    <h6>Checkout Rules</h6>
                    <p>Checks and options at the payment step.</p>
                </div>
            </div>
            <div class="scx-body">
                <div class="scx-toggles">
                    @include('livewire.settings.partials.sale-toggle', $yesNo('validate_unit_price_against_mrp', 'Validate Unit Price Against MRP', 'Block selling above the product&rsquo;s MRP.'))
                    @include('livewire.settings.partials.sale-toggle', $yesNo('prevent_out_of_stock_sales', 'Prevent Out Of Stock Sales', 'Completed sales cannot take stock below zero.'))
                    @include('livewire.settings.partials.sale-toggle', $yesNo('enable_tip', 'Enable Tip', 'Show &ldquo;Add a Tip&rdquo; on the payment screens (web and mobile).'))
                    @include('livewire.settings.partials.sale-toggle', $yesNo('round_off_enabled', 'Round Off', 'Round the grand total to a whole number; the difference shows as &ldquo;Round Off&rdquo;.'))
                </div>
            </div>
        </section>

        {{-- 3 · Staff & day sessions --}}
        <section class="scx-section">
            <div class="scx-head">
                <span class="scx-ic" style="--tone:#2f9e62"><i class="fa fa-users"></i></span>
                <div>
                    <h6>Staff &amp; Day Sessions</h6>
                    <p>Who appears on the POS and how the business day opens and closes.</p>
                </div>
            </div>
            <div class="scx-body">
                <div class="scx-toggles">
                    @include('livewire.settings.partials.sale-toggle', $yesNo('show_colleague', 'Show Colleague', 'Let staff pick a colleague on the sale.'))
                    @include('livewire.settings.partials.sale-toggle', $yesNo('branch_wise_employee_list', 'Branch Wise Employee List', 'The POS employee list shows only staff of the current branch.'))
                    @include('livewire.settings.partials.sale-toggle', $yesNo('auto_open_day_sessions_enabled', 'Auto Open Day Sessions', 'Open every branch at the opening time in <a href="'.route('settings::working_day::index').'" target="_blank">Working Day</a> with 0 opening amount. Holidays and already-open branches are skipped.'))
                    @include('livewire.settings.partials.sale-toggle', $yesNo('auto_close_day_sessions_enabled', 'Auto Close Day Sessions', 'Close all open sessions at midnight, closing amount = expected amount.'))
                </div>
            </div>
        </section>

        {{-- 4 · POS screen --}}
        <section class="scx-section">
            <div class="scx-head">
                <span class="scx-ic" style="--tone:#7c4fd6"><i class="fa fa-desktop"></i></span>
                <div>
                    <h6>POS Screen</h6>
                    <p>Layout and colours of the POS and its modals.</p>
                </div>
            </div>
            <div class="scx-body">
                <div class="row g-3">
                    <div class="col-md-6 col-xl-4">
                        <label class="form-label" for="pos_grid_columns">Products Per Row</label>
                        {{ html()->select('pos_grid_columns', posGridColumns())->value('')->class('form-select')->placeholder('How many product cards per row?')->attribute('wire:model', 'pos_grid_columns') }}
                        <div class="form-text">&ldquo;Auto&rdquo; fits the screen. Phones always show 2.</div>
                    </div>
                </div>

                <div class="scx-sub mt-4">Colour preset</div>
                <div class="form-text mt-n2 mb-2">&ldquo;Follow App Theme&rdquo; uses the colour from Settings &rarr; Theme.</div>
                <div class="row g-2 pos-preset-picker">
                    @foreach (posColorPresets() as $key => $preset)
                        <div class="col-6 col-md-4 col-xl-3">
                            <label class="pos-preset {{ $pos_color_preset === $key ? 'active' : '' }}">
                                <input type="radio" class="d-none" value="{{ $key }}" wire:model.live="pos_color_preset">
                                <span class="pos-preset-swatch">
                                    <span class="pos-preset-bar" style="background: {{ $preset['primary'] }}"></span>
                                    <span class="pos-preset-body" style="background: {{ $preset['canvas'] }}">
                                        <span class="pos-preset-card" style="background: {{ $preset['panel'] }}; border-color: {{ $preset['line'] }}">
                                            <span class="pos-preset-dot" style="background: {{ $preset['accent'] }}"></span>
                                            <span class="pos-preset-line" style="background: {{ $preset['line'] }}"></span>
                                        </span>
                                    </span>
                                </span>
                                <span class="pos-preset-meta">
                                    <span class="pos-preset-name">{{ $preset['name'] }}</span>
                                    <span class="pos-preset-note">{{ $preset['note'] }}</span>
                                </span>
                                <i class="fa fa-check-circle pos-preset-check"></i>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- 5 · Receipt --}}
        <section class="scx-section">
            <div class="scx-head">
                <span class="scx-ic" style="--tone:#d4931c"><i class="fa fa-print"></i></span>
                <div>
                    <h6>Receipt</h6>
                    <p>The thermal receipt printed after a sale.</p>
                </div>
            </div>
            <div class="scx-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="thermal_printer_style">Thermal Printer Style</label>
                        {{ html()->select('thermal_printer_style', thermalPrinterStyle())->value('')->class('form-select')->placeholder('Select Printer Style')->attribute('wire:model', 'thermal_printer_style') }}
                    </div>
                    <div class="col-md-6">
                        {{-- Saved in this browser, not with the settings: each till picks its own printer. --}}
                        <div wire:ignore>
                            <label class="form-label">Receipt Printer <span class="fw-normal text-body-secondary">(this computer)</span></label>
                            <button type="button" class="btn btn-outline-secondary w-100 d-flex align-items-center gap-2" data-receipt-printer-choose>
                                <i class="fa fa-print"></i>
                                <span class="flex-grow-1 text-start text-truncate" data-receipt-printer-name>Printer</span>
                                <i class="fa fa-cog"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="print_item_label">Item Label</label>
                        {{ html()->select('print_item_label', ['product' => 'Product Name', 'category' => 'Category Name'])->value('')->class('form-select')->placeholder('Select what to print per item')->attribute('wire:model', 'print_item_label') }}
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="print_quantity_label">Quantity Label</label>
                        {{ html()->select('print_quantity_label', ['quantity' => 'Quantity', 'weight' => 'Weight'])->value('')->class('form-select')->placeholder('Select label for item quantity')->attribute('wire:model', 'print_quantity_label') }}
                    </div>
                </div>

                <div class="scx-sub mt-4">Show on receipt</div>
                <div class="scx-toggles">
                    @include('livewire.settings.partials.sale-toggle', $yesNo('enable_logo_in_print', 'Logo'))
                    @include('livewire.settings.partials.sale-toggle', $yesNo('enable_company_name_in_print', 'Company Name'))
                    @include('livewire.settings.partials.sale-toggle', $yesNo('enable_customer_mobile_in_print', 'Customer Mobile'))
                    @include('livewire.settings.partials.sale-toggle', $yesNo('enable_barcode_in_print', 'Barcode'))
                    @include('livewire.settings.partials.sale-toggle', $yesNo('enable_discount_in_print', 'Discount'))
                    @include('livewire.settings.partials.sale-toggle', $yesNo('enable_total_quantity_in_print', 'Total Quantity'))
                </div>

                <div class="scx-sub mt-4">Footer message</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="thermal_printer_footer_english">English</label>
                        {{ html()->input('thermal_printer_footer_english')->value('')->class('form-control')->placeholder('e.g. Thank you for shopping with us')->attribute('wire:model', 'thermal_printer_footer_english') }}
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="thermal_printer_footer_arabic">Arabic</label>
                        {{ html()->input('thermal_printer_footer_arabic')->value('')->class('form-control')->attribute('dir', 'rtl')->placeholder('رسالة التذييل')->attribute('wire:model', 'thermal_printer_footer_arabic') }}
                    </div>
                </div>
            </div>
        </section>

        <div class="scx-bar">
            <span><i class="fa fa-info-circle me-1"></i>Changes apply after you save.</span>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save"><i class="fa fa-save me-1"></i>Save Changes</span>
                <span wire:loading wire:target="save"><i class="fa fa-spinner fa-spin me-1"></i>Saving…</span>
            </button>
        </div>
    </form>

    @push('styles')
        <style>
            .pos-preset-picker .pos-preset {
                position: relative;
                display: flex;
                align-items: center;
                gap: .625rem;
                width: 100%;
                margin: 0;
                padding: .5rem;
                border: 1px solid var(--bs-border-color);
                border-radius: .625rem;
                background: var(--bs-component-bg, var(--bs-body-bg));
                cursor: pointer;
                transition: border-color .15s ease, box-shadow .15s ease;
            }

            .pos-preset-picker .pos-preset:hover {
                border-color: var(--bs-primary);
            }

            .pos-preset-picker .pos-preset.active {
                border-color: var(--bs-primary);
                box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), .15);
            }

            .pos-preset-swatch {
                flex: 0 0 auto;
                width: 54px;
                height: 40px;
                overflow: hidden;
                border: 1px solid var(--bs-border-color);
                border-radius: .375rem;
            }

            .pos-preset-bar {
                display: block;
                height: 11px;
            }

            .pos-preset-body {
                display: block;
                height: 29px;
                padding: 4px;
            }

            .pos-preset-card {
                display: flex;
                align-items: center;
                gap: 3px;
                height: 100%;
                padding: 0 4px;
                border: 1px solid;
                border-radius: 3px;
            }

            .pos-preset-dot {
                width: 7px;
                height: 7px;
                border-radius: 50%;
            }

            .pos-preset-line {
                flex: 1;
                height: 3px;
                border-radius: 2px;
                opacity: .8;
            }

            .pos-preset-meta {
                min-width: 0;
                line-height: 1.25;
            }

            .pos-preset-name {
                display: block;
                font-size: .8125rem;
                font-weight: 600;
                color: var(--bs-emphasis-color);
            }

            .pos-preset-note {
                display: block;
                font-size: .6875rem;
                color: var(--bs-secondary-color);
            }

            .pos-preset-check {
                position: absolute;
                top: .375rem;
                inset-inline-end: .5rem;
                color: var(--bs-primary);
                opacity: 0;
                transition: opacity .15s ease;
            }

            .pos-preset.active .pos-preset-check {
                opacity: 1;
            }
        </style>
    @endpush
</div>

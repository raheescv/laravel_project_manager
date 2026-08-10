<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white py-2">
        <h5 class="mb-0 text-white">Sale Configuration Settings</h5>
    </div>
    <form wire:submit="save">
        <div class="card-body p-3">
            <div class="row g-2">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-medium" for="sale_type">Sale Type</label>
                        {{ html()->select('sale_type', saleTypes())->value('')->class('form-select')->placeholder('Select Sale Type')->attribute('wire:model', 'sale_type') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-medium" for="default_customer_enabled">Default Customer</label>
                        {{ html()->select('default_customer_enabled', ['yes' => 'Yes', 'no' => 'No'])->value('')->class('form-select')->placeholder('Use General Customer by default?')->attribute('wire:model', 'default_customer_enabled') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-medium" for="default_product_type">Default Product Type</label>
                        {{ html()->select('default_product_type', ['product' => 'Products', 'service' => 'Services', '' => 'All Types'])->value('')->class('form-select')->placeholder('Select Default Product Type')->attribute('wire:model', 'default_product_type') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-medium" for="default_status">Default Status</label>
                        {{ html()->select('default_status', saleStatuses())->value('')->class('form-select')->placeholder('Select Default Status')->attribute('wire:model', 'default_status') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-medium" for="thermal_printer_style">Thermal Printer Style</label>
                        {{ html()->select('thermal_printer_style', thermalPrinterStyle())->value('')->class('form-select')->placeholder('Select Printer Style')->attribute('wire:model', 'thermal_printer_style') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-medium" for="enable_discount_in_print">Enable Discount In Print</label>
                        {{ html()->select('enable_discount_in_print', ['yes' => 'Yes', 'no' => 'No'])->value('')->class('form-select')->placeholder('Select Option')->attribute('wire:model', 'enable_discount_in_print') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-medium" for="enable_total_quantity_in_print">Enable Total Quantity In Print</label>
                        {{ html()->select('enable_total_quantity_in_print', ['yes' => 'Yes', 'no' => 'No'])->value('')->class('form-select')->placeholder('Select Option')->attribute('wire:model', 'enable_total_quantity_in_print') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-medium" for="enable_logo_in_print">Enable Logo In Print</label>
                        {{ html()->select('enable_logo_in_print', ['yes' => 'Yes', 'no' => 'No'])->value('')->class('form-select')->placeholder('Select Option')->attribute('wire:model', 'enable_logo_in_print') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-medium" for="enable_company_name_in_print">Enable Company Name In Print</label>
                        {{ html()->select('enable_company_name_in_print', ['yes' => 'Yes', 'no' => 'No'])->value('')->class('form-select')->placeholder('Select Option')->attribute('wire:model', 'enable_company_name_in_print') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-medium" for="enable_barcode_in_print">Enable Barcode In Print</label>
                        {{ html()->select('enable_barcode_in_print', ['yes' => 'Yes', 'no' => 'No'])->value('')->class('form-select')->placeholder('Select Option')->attribute('wire:model', 'enable_barcode_in_print') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-medium" for="print_item_label">Item Label In Print</label>
                        {{ html()->select('print_item_label', ['product' => 'Product Name', 'category' => 'Category Name'])->value('')->class('form-select')->placeholder('Select what to print per item')->attribute('wire:model', 'print_item_label') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-medium" for="print_quantity_label">Quantity Label In Print</label>
                        {{ html()->select('print_quantity_label', ['quantity' => 'Quantity', 'weight' => 'Weight'])->value('')->class('form-select')->placeholder('Select label for item quantity')->attribute('wire:model', 'print_quantity_label') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-medium" for="default_quantity">Default Quantity</label>
                        {{ html()->input('number', 'default_quantity')->value('')->class('form-control')->attribute('step', '0.001')->placeholder('Enter default quantity (e.g., 0.001)')->attribute('wire:model', 'default_quantity') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-medium" for="validate_unit_price_against_mrp">Validate Unit Price Against MRP</label>
                        {{ html()->select('validate_unit_price_against_mrp', ['yes' => 'Yes', 'no' => 'No'])->value('')->class('form-select')->placeholder('Validate unit price against MRP?')->attribute('wire:model', 'validate_unit_price_against_mrp') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-medium" for="show_colleague">Show Colleague</label>
                        {{ html()->select('show_colleague', ['yes' => 'Yes', 'no' => 'No'])->value('')->class('form-select')->placeholder('Do You want to show colleague?')->attribute('wire:model', 'show_colleague') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-medium" for="branch_wise_employee_list">Branch Wise Employee List</label>
                        {{ html()->select('branch_wise_employee_list', ['yes' => 'Yes', 'no' => 'No'])->value('')->class('form-select')->placeholder('Show employees of current branch only?')->attribute('wire:model', 'branch_wise_employee_list') }}
                        <small class="form-text text-muted">When enabled, POS employee dropdown will show only employees assigned to the current branch.</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-medium" for="auto_close_day_sessions_enabled">Auto Close Day Sessions</label>
                        {{ html()->select('auto_close_day_sessions_enabled', ['yes' => 'Yes', 'no' => 'No'])->value('')->class('form-select')->placeholder('Enable automatic daily closing of day sessions?')->attribute('wire:model', 'auto_close_day_sessions_enabled') }}
                        <small class="form-text text-muted">When enabled, all open day sessions will be automatically closed daily at midnight with closing amount set to expected amount.</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-medium" for="sale_item_row_mode">Same Product Cart Rows</label>
                        {{ html()->select('sale_item_row_mode', ['merge' => 'Single Row (merge quantity)', 'separate' => 'Multiple Rows (add separately)'])->value('')->class('form-select')->placeholder('Choose how repeated product clicks behave')->attribute('wire:model', 'sale_item_row_mode') }}
                        <small class="form-text text-muted">Controls whether clicking the same product card adds quantity to the existing cart row or creates a new row.</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-medium" for="prevent_out_of_stock_sales">Prevent Out Of Stock Sales</label>
                        {{ html()->select('prevent_out_of_stock_sales', ['yes' => 'Yes', 'no' => 'No'])->value('')->class('form-select')->placeholder('Block sale completion when stock is not enough?')->attribute('wire:model', 'prevent_out_of_stock_sales') }}
                        <small class="form-text text-muted">When enabled, completed sales cannot reduce inventory below zero.</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-medium" for="enable_tip">Enable Tip</label>
                        {{ html()->select('enable_tip', ['yes' => 'Yes', 'no' => 'No'])->value('')->class('form-select')->placeholder('Show the "Add a Tip" option at checkout?')->attribute('wire:model', 'enable_tip') }}
                        <small class="form-text text-muted">When disabled, the "Add a Tip" option is hidden on the sale payment screens (web and mobile app).</small>
                    </div>
                </div>
                <div class="col-12">
                    <hr class="my-2">
                    <label class="form-label fw-medium d-block mb-1">POS Colour Preset</label>
                    <small class="form-text text-muted d-block mb-2">
                        Sets the palette of the POS screen and all of its modals. Each preset is a complete
                        combination &mdash; one primary for structure, one accent for favourites and money.
                        &ldquo;Follow App Theme&rdquo; uses the colour picked in Settings &rarr; Theme.
                    </small>
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
                    <hr class="my-2">
                </div>
                <div class="col-12">
                    <div class="form-group">
                        <label class="form-label fw-medium" for="thermal_printer_footer_english">Thermal Printer Footer (English)</label>
                        {{ html()->input('thermal_printer_footer_english')->value('')->class('form-control')->placeholder('Enter your printer footer message')->attribute('wire:model', 'thermal_printer_footer_english') }}
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-group">
                        <label class="form-label fw-medium" for="thermal_printer_footer_arabic">Thermal Printer Footer (Arabic)</label>
                        {{ html()->input('thermal_printer_footer_arabic')->value('')->class('form-control')->attribute('dir', 'rtl')->placeholder('Enter your printer footer message')->attribute('wire:model', 'thermal_printer_footer_arabic') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-light text-end py-2 px-3">
            <button type="submit" class="btn btn-primary btn-sm px-3">
                <i class="fa fa-save me-1"></i>Save Changes
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
                background: var(--bs-body-bg);
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

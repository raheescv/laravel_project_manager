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
</div>

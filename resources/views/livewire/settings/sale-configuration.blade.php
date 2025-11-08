<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0 text-white">Sale Configuration Settings</h4>
                </div>
                <form wire:submit="save">
                    <div class="card-body">
                        <div class="row g-3">
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
                                    {{ html()->input('number', 'default_quantity')->value('')->class('form-control')->attribute('step','0.001')->placeholder('Enter default quantity (e.g., 0.001)')->attribute('wire:model', 'default_quantity') }}
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
                    <div class="card-footer bg-light text-end py-3">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fa fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

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

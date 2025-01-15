<div>
    <div class="col-md-8">
        <form wire:submit="save">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label class="text-capitalize" for="sale_type">Sale Type</label>
                        {{ html()->select('sale_type', saleTypes())->value('')->class('form-control')->placeholder('Select Any')->attribute('wire:model', 'sale_type') }}
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label class="text-capitalize" for="default_status">Default Status</label>
                        {{ html()->select('default_status', saleStatuses())->value('')->class('form-control')->placeholder('Select Any')->attribute('wire:model', 'default_status') }}
                    </div>
                </div>
                <div class="row my-3">
                    <div class="col-md-6">
                        <label class="text-capitalize" for="thermal_printer_style">Thermal Printer Style</label>
                        {{ html()->select('thermal_printer_style', thermalPrinterStyle())->value('')->class('form-control')->placeholder('Select Any')->attribute('wire:model', 'thermal_printer_style') }}
                    </div>
                </div>
                <div class="row my-3">
                    <div class="col-md-6">
                        <label class="text-capitalize" for="enable_discount_in_print">Enable Discount In Print</label>
                        {{ html()->select('enable_discount_in_print', ['yes' => 'Yes', 'no' => 'No'])->value('')->class('form-control')->placeholder('Select Any')->attribute('wire:model', 'enable_discount_in_print') }}
                    </div>
                </div>
                <div class="row my-3">
                    <div class="col-md-6">
                        <label class="text-capitalize" for="enable_total_quantity_in_print">Enable Total Quantity In Print</label>
                        {{ html()->select('enable_total_quantity_in_print', ['yes' => 'Yes', 'no' => 'No'])->value('')->class('form-control')->placeholder('Select Any')->attribute('wire:model', 'enable_total_quantity_in_print') }}
                    </div>
                </div>
                <div class="row my-3">
                    <div class="col-md-6">
                        <label class="text-capitalize" for="enable_logo_in_print">enable logo in print</label>
                        {{ html()->select('enable_logo_in_print', ['yes' => 'Yes', 'no' => 'No'])->value('')->class('form-control')->placeholder('Select Any')->attribute('wire:model', 'enable_logo_in_print') }}
                    </div>
                </div>
                <div class="row my-3">
                    <div class="col-md-12">
                        <label class="text-capitalize" for="thermal_printer_footer_english">Thermal Printer Footer English</label>
                        {{ html()->input('thermal_printer_footer_english')->value('')->class('form-control')->placeholder('Enter Your Printer Footer Message')->attribute('wire:model', 'thermal_printer_footer_english') }}
                    </div>
                </div>
                <div class="row my-3">
                    <div class="col-md-12">
                        <label class="text-capitalize" for="thermal_printer_footer_arabic">Thermal Printer Footer Arabic</label>
                        {{ html()->input('thermal_printer_footer_arabic')->value('')->class('form-control')->attribute('dir', 'rtl')->placeholder('Enter Your Printer Footer Message')->attribute('wire:model', 'thermal_printer_footer_arabic') }}
                    </div>
                </div>
            </div>
            <div class="card-footer"> <br>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

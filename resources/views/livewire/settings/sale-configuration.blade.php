<div>
    <div class="col-md-6">
        <form wire:submit="save">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <label for="default_status">Default Status</label>
                        {{ html()->select('default_status', saleStatuses())->value('')->class('form-control')->placeholder('Select Any')->attribute('wire:model', 'default_status') }}
                    </div>
                </div>
            </div>
            <div class="card-footer"> <br>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<div>

    <div class="row">
        <form wire:submit="save">
            <div class="col-md-6 mb-3">
                <div class="row">
                    <div class="col-md-12">
                        <div class="row g-1 mb-3">
                            <x-filepond::upload wire:model="logo" multiple max-files="1" />
                        </div>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn  btn-sm btn-success" style="float: right;margin-right:5px; ">Save</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <img src="{{ $uploaded_logo }}" width="100%" height="100%" alt="Logo">
                    </div>
                </div>
            </div>
        </form>
    </div>
    @push('scripts')
        @filepondScripts
    @endpush
</div>

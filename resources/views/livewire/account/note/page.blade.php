<div>
    <div class="modal-header">
        <h5 class="modal-title">{{ $table_id ? 'Edit Notes' : 'Add Notes' }}</h5>
        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form wire:submit="save">
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    @if ($this->getErrorBag()->count())
                        <ol>
                            <?php foreach ($this->getErrorBag()->toArray() as $value): ?>
                            <li style="color:red">* {{ $value[0] }}</li>
                            <?php endforeach; ?>
                        </ol>
                    @endif
                </div>
            </div>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Notes <span class="text-danger">*</span></label>
                    <textarea wire:model="notes.note" class="form-control" rows="3"></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    {{ html()->select('type', noteTypes())->value('')->class('form-control')->attribute('wire:model', 'notes.type')->placeholder('Please Select  Type')->id('modal_type') }}
                </div>
                <div class="col-md-6">
                    <label class="form-label">Follow Up Date</label>
                    <input type="date" wire:model="notes.follow_up_date" class="form-control">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" wire:click="save('completed')" class="btn btn-success">Save as Completed</button>
            <button type="submit" class="btn btn-primary">Save as Pending</button>
        </div>
    </form>
</div>

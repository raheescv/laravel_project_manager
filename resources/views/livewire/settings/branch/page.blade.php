<div>
    <div class="modal-header">
        <h1 class="modal-title fs-5">Branch Modal</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <h4> <label for="code">Code</label> </h4>
                        {{ html()->input('code')->value('')->class('form-control')->required(true)->attribute('wire:model', 'branches.code') }}
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-group">
                        <h4> <label for="name">Name</label> </h4>
                        {{ html()->input('name')->value('')->class('form-control')->required(true)->attribute('wire:model', 'branches.name') }}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <h4> <label for="code">Location</label> </h4>
                        {{ html()->input('location')->value('')->class('form-control')->attribute('wire:model', 'branches.location') }}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <h4> <label for="code">Mobile</label> </h4>
                        {{ html()->input('mobile')->value('')->class('form-control')->attribute('wire:model', 'branches.mobile') }}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <div class="moq-sync-container">
                            <div class="custom-checkbox-wrapper"> <br>
                                {{ html()->checkbox('moq_sync')->class('form-check-input moq-sync-checkbox')->attribute('wire:model', 'branches.moq_sync')->id('moq_sync') }}
                                <label for="moq_sync" class="form-check-label moq-sync-label">
                                    <span class="checkbox-icon">
                                        <i class="fas fa-sync-alt"></i>
                                    </span>
                                    <span class="checkbox-text">
                                        <strong>MOQ Sync</strong>
                                        <small class="text-muted d-block">Synchronize with MOQ Service</small>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" wire:click="save(1)" class="btn btn-success">Save & Add New</button>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>

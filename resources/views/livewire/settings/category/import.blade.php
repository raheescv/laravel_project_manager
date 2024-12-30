<div>
    <div class="modal-header">
        <h1 class="modal-title fs-5" id="CategoryModalLabel">Category Modal</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form wire:submit="save">
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    @if ($this->getErrorBag()->count())
                        <ol>
                            <?php foreach ($this->getErrorBag()->toArray() as $key => $value): ?>
                            <li style="color:red">* {{ $value[0] }}</li>
                            <?php endforeach; ?>
                        </ol>
                    @endif
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <h4> <label for="name">Name</label> </h4>
                        <input type="file" wire:model="file" class="form-control">
                        @error('file')
                            <span class="error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="progress progress-xl">
                    <div class="progress-bar bg-info" id="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0</div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary" @if (!$file) disabled @endif>Import</button>
        </div>
    </form>
    @push('scripts')
        <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
        <script>
            Pusher.logToConsole = false;
            const pusher = new Pusher("{{ env('PUSHER_APP_KEY') }}", {
                cluster: "{{ env('PUSHER_APP_CLUSTER') }}",
                encrypted: true,
            });
            const channel = pusher.subscribe('file-import-channel-{{ auth()->id() }}');
            channel.bind('file-import-event-{{ auth()->id() }}', function(data) {
                if (data.type === 'Category') {
                    const progressBar = document.getElementById('progress-bar');
                    progressBar.style.width = `${data.progress}%`;
                    progressBar.setAttribute('aria-valuenow', data.progress);
                    $('#progress-bar').text(Math.round(data.progress, 2));
                    if (data.progress === 100) {
                        alert('Import completed!');
                        window.location.reload();
                    }
                }
            });
        </script>
    @endpush
</div>

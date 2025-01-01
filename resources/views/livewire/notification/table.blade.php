<div>
    <div class="card-header -4 mb-3">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">

            </div>
            <div class="col-md-6 d-flex gap-1 align-items-center justify-content-md-end mb-3">
                <div class="form-group">
                    <select wire:model.live="limit" class="form-control">
                        <option value="10">10</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                </div>
                <div class="form-group">
                    <input type="text" wire:model.live="search" autofocus placeholder="Search..." class="form-control" autocomplete="off">
                </div>
            </div>
        </div>
    </div>
    <div class="card-header">
        <div class="row">
            <div class="col-md-3">
                <h4> <label for="start_date">Start Date</label> </h4>
                {{ html()->date('start_date')->value('')->class('form-control')->attribute('wire:model.live', 'start_date') }}
            </div>
            <div class="col-md-3">
                <h4> <label for="end_date">End Date</label> </h4>
                {{ html()->date('end_date')->value('')->class('form-control')->attribute('wire:model.live', 'end_date') }}
            </div>
            <div class="col-md-3">
                <h4> <label for="type">Type</label> </h4>
                {{ html()->select('type', $types)->value('')->class('form-control')->placeholder('All')->attribute('wire:model.live', 'type') }}
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle table-sm">
                <thead>
                    <tr class="text-capitalize">
                        <th> # </th>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Message</th>
                        <th>File</th>
                        <th>Received At</th>
                        <th>Read At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            @php
                                $item->markAsRead();
                                $itemData = $item['data'];
                                $file_path = $itemData['file_path'] ?? '';
                                $type = explode('\\', $item->type);
                            @endphp
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ end($type) }}</td>
                            <td>{{ $itemData['title'] ?? '' }}</td>
                            <td>{{ $itemData['message'] ?? '' }}</td>
                            <td>
                                @if ($file_path)
                                    <a href="{{ url($file_path) }}" download><i class="demo-psi-download-from-cloud fs-5 me-2 pointer"></i></a>
                                @endif
                            </td>
                            <td>{{ $item->created_at->diffForHumans() }}</td>
                            <td>{{ $item->read_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $data->links() }}
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#user_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('user_id', value);
                });
            });
        </script>
    @endpush
</div>

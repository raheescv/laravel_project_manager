<div>
    <div class="card-header bg-light p-3 mb-3">
        <div class="row align-items-center">
            <div class="col-md-6 mb-3 mb-md-0">
                <h4 class="mb-0">
                    <i class="demo-psi-bell text-primary me-2"></i>
                    Notifications
                </h4>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-2 justify-content-md-end">
                    <select wire:model.live="limit" class="form-select w-auto">
                        <option value="10">10 rows</option>
                        <option value="100">100 rows</option>
                        <option value="500">500 rows</option>
                    </select>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="demo-pli-magnifi-glass"></i>
                        </span>
                        <input type="text" wire:model.live="search" class="form-control border-start-0 ps-0" placeholder="Search notifications..." autofocus autocomplete="off">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body bg-light rounded-3 mb-3">
        <div class="row g-3">
            <div class="col-md-3">
                <label for="start_date" class="form-label fw-medium text-dark">
                    <i class="demo-pli-calendar-4 me-1"></i>
                    Start Date
                </label>
                {{ html()->date('start_date')->value('')->class('form-control')->attribute('wire:model.live', 'start_date') }}
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label fw-medium text-dark">
                    <i class="demo-pli-calendar-4 me-1"></i>
                    End Date
                </label>
                {{ html()->date('end_date')->value('')->class('form-control')->attribute('wire:model.live', 'end_date') }}
            </div>
            <div class="col-md-3">
                <label for="type" class="form-label fw-medium text-dark">
                    <i class="demo-pli-tag me-1"></i>
                    Type
                </label>
                {{ html()->select('type', $types)->value('')->class('form-select')->placeholder('All Types')->attribute('wire:model.live', 'type') }}
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check">
                    <label class="form-check-label d-flex align-items-center gap-2" for="unread_only">
                        {{ html()->checkbox('unread_only')->value('')->class('form-check-input')->attribute('wire:model.live', 'unread_only') }}
                        <span>
                            <i class="demo-pli-mail me-1"></i>
                            Unread Only
                        </span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="card rounded-3 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="text-capitalize">
                            <th class="py-3">#</th>
                            <th class="py-3">Type</th>
                            <th class="py-3">Title</th>
                            <th class="py-3">Message</th>
                            <th class="py-3">File</th>
                            <th class="py-3">Received</th>
                            <th class="py-3">Read</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $item)
                            @php
                                $item->markAsRead();
                                $itemData = $item['data'];
                                $file_path = $itemData['file_path'] ?? '';
                                $type = explode('\\', $item->type);
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary">
                                        {{ end($type) }}
                                    </span>
                                </td>
                                <td class="fw-medium">{{ $itemData['title'] ?? '' }}</td>
                                <td>{{ $itemData['message'] ?? '' }}</td>
                                <td>
                                    @if ($file_path)
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('notification::excel-view', ['path' => $file_path]) }}" target="_blank" class="btn btn-sm btn-light" title="View Excel">
                                                <i class="demo-pli-file-excel fs-6"></i>
                                            </a>
                                            <a href="{{ url('storage/'.$file_path) }}" download class="btn btn-sm btn-light" title="Download">
                                                <i class="demo-psi-download-from-cloud fs-6"></i>
                                            </a>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-muted" title="{{ $item->created_at }}">
                                        {{ $item->created_at->diffForHumans() }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-muted" title="{{ $item->read_at }}">
                                        {{ $item->read_at->diffForHumans() }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $data->links() }}
    </div>
</div>

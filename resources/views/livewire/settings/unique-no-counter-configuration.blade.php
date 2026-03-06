<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white py-2">
        <h5 class="mb-0 text-white">Unique Number Counter Configuration</h5>
    </div>

    <form wire:submit="save">
        <div class="card-body p-3">
            <p class="text-muted small mb-3">
                Update only the current counter values. Year, branch code, and segment are fixed keys.
            </p>

            @if (empty($rows))
                <div class="alert alert-info mb-0">
                    No counter rows found for the current tenant.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-nowrap">Year</th>
                                <th class="text-nowrap">Branch Code</th>
                                <th class="text-nowrap">Segment</th>
                                <th class="text-nowrap">Current Number</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $index => $row)
                                <tr>
                                    <td class="text-nowrap">{{ $row['year'] }}</td>
                                    <td class="text-nowrap">{{ $row['branch_code'] }}</td>
                                    <td class="text-nowrap">{{ $row['segment'] }}</td>
                                    <td>
                                        {{ html()->input('number')->class('form-control form-control-sm')->attribute('wire:model', 'rows.' . $index . '.number')->attribute('min', 0)->attribute('step', 1) }}
                                        @error('rows.' . $index . '.number')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="card-footer bg-light text-end py-2 px-3">
            <button type="submit" class="btn btn-primary btn-sm px-3" @disabled(empty($rows))>
                <i class="fa fa-save me-1"></i>Update Counters
            </button>
        </div>
    </form>
</div>

@props(['audits', 'columns'])

<div class="table-responsive">
    <table class="table table-striped align-middle table-bordered table-sm mb-0">
        <thead>
            <tr class="bg-primary text-white">
                <th class="text-white text-nowrap">Date Time</th>
                <th class="text-white">User</th>
                <th class="text-white">Event</th>
                @foreach ($columns as $key)
                    <th class="text-white text-end text-nowrap">{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $key)) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($audits as $audit)
                <tr>
                    <td class="text-nowrap">{{ $audit->created_at }}</td>
                    <td>{{ $audit->user?->name ?? '-' }}</td>
                    <td>{{ $audit->event }}</td>
                    @foreach ($columns as $key)
                        @php
                            $oldValueExists = array_key_exists($key, $audit->old_values ?? []);
                            $newValueExists = array_key_exists($key, $audit->new_values ?? []);
                            $oldValue = $audit->old_values[$key] ?? null;
                            $newValue = $audit->new_values[$key] ?? null;
                        @endphp
                        <td class="text-end text-nowrap">
                            @if ($oldValueExists && $newValueExists && $oldValue !== $newValue)
                                <span class="text-danger">{{ is_scalar($oldValue) ? $oldValue : json_encode($oldValue) }}</span>
                                <i class="fa fa-arrow-right text-muted mx-1"></i>
                                <span class="text-success">{{ is_scalar($newValue) ? $newValue : json_encode($newValue) }}</span>
                            @elseif ($newValueExists)
                                {{ is_scalar($newValue) ? $newValue : json_encode($newValue) }}
                            @else
                                -
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) + 3 }}" class="text-center text-muted py-3">No audit entries found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

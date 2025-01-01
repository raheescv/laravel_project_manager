<div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle table-sm">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Url</th>
                        <th>Old Values</th>
                        <th>New Values</th>
                        <th>User</th>
                        <th class="text-end">Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($audits as $item)
                        <tr>
                            <td>{{ ucFirst($item->event) }}</td>
                            <td>{{ str_replace(url('/'), '', $item->url) }}</td>
                            <td>
                                <pre>{{ json_encode($item->old_values, JSON_PRETTY_PRINT) }}</pre>
                            </td>
                            <td>
                                <pre>{{ json_encode($item->new_values, JSON_PRETTY_PRINT) }}</pre>
                            </td>
                            <td>{{ $item->user->name }}</td>
                            <td class="text-end">{{ systemDateTime($item->created_at) }} <br> <i>{{ $item->created_at->diffForHumans() }}</i> </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

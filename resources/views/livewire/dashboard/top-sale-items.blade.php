<div class="card">
    <div class="card-header border-0 bg-transparent">
        <h4 class="mb-0">Today's Top 10 Picks</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item</th>
                        <th class="text-end">Quantity</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($topItems as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->product->name }}</td>
                            <td class="text-end">{{ $item->total_quantity }}</td>
                            <td class="text-end">{{ currency($item->total_amount) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

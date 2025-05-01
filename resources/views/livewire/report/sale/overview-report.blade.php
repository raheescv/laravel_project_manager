<div>
    <div class="card mb-3">
        <div class="card-header">
            <div class="col-lg-12">
                <div class="row">
                    <div class="col-md-2">
                        <label for="from_date">From Date</label>
                        {{ html()->date('from_date')->value('')->class('form-control')->id('from_date')->attribute('wire:model.live', 'from_date') }}
                    </div>
                    <div class="col-md-2">
                        <label for="to_date">To Date</label>
                        {{ html()->date('to_date')->value('')->class('form-control')->id('to_date')->attribute('wire:model.live', 'to_date') }}
                    </div>
                    <div class="col-md-4" wire:ignore>
                        <label for="branch_id">Branch</label>
                        {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->value(session('branch_id'))->class('select-assigned-branch_id-list')->id('branch_id')->placeholder('Branch') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-2">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm align-middle">
                            <tbody>
                                @foreach ($overview as $title => $value)
                                    <tr>
                                        <th>{{ $title }}</th>
                                        <th class="text-end">{{ currency($value) }}</th>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm align-middle">
                            <thead>
                                <tr class="text-capitalize">
                                    <th> #</th>
                                    <th> employee</th>
                                    <th class="text-end"> quantity</th>
                                    <th class="text-end"> total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($employees as $item)
                                    <tr>
                                        <th>{{ $loop->iteration }}</th>
                                        <th>{{ $item->employee }}</th>
                                        <td class="text-end">{{ currency($item->quantity) }}</td>
                                        <td class="text-end">{{ currency($item->total) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="2" class="text-end">Total</th>
                                    <th class="text-end">{{ currency($employees->sum('quantity')) }}</th>
                                    <th class="text-end">{{ currency($employees->sum('total')) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm align-middle">
                            <thead>
                                <tr class="text-capitalize">
                                    <th> #</th>
                                    <th> product / service</th>
                                    <th class="text-end"> quantity</th>
                                    <th class="text-end"> total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($products as $item)
                                    <tr>
                                        <th>{{ $loop->iteration }}</th>
                                        <th>{{ $item->product }}</th>
                                        <td class="text-end">{{ currency($item->quantity) }}</td>
                                        <td class="text-end">{{ currency($item->total) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="2" class="text-end">Total</th>
                                    <th class="text-end">{{ currency($products->sum('quantity')) }}</th>
                                    <th class="text-end">{{ currency($products->sum('total')) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#branch_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('branch_id', value);
                });
            });
        </script>
    @endpush
</div>

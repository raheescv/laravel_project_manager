<div class="card-body">
    <div class="row">
        <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
            <div class="btn-group">
                <button class="btn btn-icon btn-outline-light" title="Export to Excel" wire:click="export">
                    <i class="demo-pli-file-excel fs-5"></i>
                </button>
            </div>
        </div>
        <div class="col-md-6 d-flex gap-1 align-items-center justify-content-md-end mb-3">
            <div class="form-group">
                <select wire:model.live="limit" class="form-control">
                    <option value="10">10</option>
                    <option value="100">100</option>
                    <option value="500">500</option>
                </select>
            </div>
        </div>
    </div>
    <hr>

    <div class="row mb-3">
        <div class="col-md-3">
            <div class="form-group">
                <label for="from_date">From Date</label>
                {{ html()->date('from_date')->value('')->class('form-control')->attribute('wire:model.live', 'from_date') }}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="to_date">To Date</label>
                {{ html()->date('to_date')->value('')->class('form-control')->attribute('wire:model.live', 'to_date') }}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="prompt">Analysis Prompt</label>
                <input type="text" wire:model="prompt" class="form-control" placeholder="Enter your analysis request...">
            </div>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="button" wire:click="generateReport" class="btn btn-primary">
                Generate Report
            </button>
        </div>
    </div>

    @if (session('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if ($analysis)
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">AI Analysis</h5>
            </div>
            <div class="card-body">
                <div class="analysis-content">
                    {!! nl2br(e($analysis)) !!}
                </div>
            </div>
        </div>
    @endif

    @if (count($data) > 0)
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Invoice No</th>
                        <th>Product</th>
                        <th class="text-end">Quantity</th>
                        <th class="text-end">Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            <td>{{ $item['date'] }}</td>
                            <td>{{ $item['invoice'] }}</td>
                            <td>{{ $item['product'] }}</td>
                            <td class="text-end">{{ number_format($item['quantity']) }}</td>
                            <td class="text-end">{{ currency($item['total']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="alert alert-info">
            Enter a prompt and generate the report to see data here.
        </div>
    @endif
</div>

<div>
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">{{ __('Purchase Return Details') }}</h3>
                <div>
                    @can('purchase return.edit')
                        <a href="{{ route('purchase_return::edit', $purchaseReturn->id) }}" class="btn btn-primary">
                            <i class="fa fa-edit"></i> {{ __('Edit') }}
                        </a>
                    @endcan
                    <a href="{{ route('purchase_return::index') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>{{ __('Purchase Return Information') }}</h5>
                    <table class="table table-borderless">
                        <tr>
                            <th>{{ __('Reference No') }}:</th>
                            <td>{{ $purchaseReturn->reference_no }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Date') }}:</th>
                            <td>{{ $purchaseReturn->date }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Status') }}:</th>
                            <td>{{ ucfirst($purchaseReturn->status) }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5>{{ __('Vendor Information') }}</h5>
                    <table class="table table-borderless">
                        <tr>
                            <th>{{ __('Name') }}:</th>
                            <td>{{ $purchaseReturn->account->name }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Email') }}:</th>
                            <td>{{ $purchaseReturn->account->email }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Phone') }}:</th>
                            <td>{{ $purchaseReturn->account->mobile }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <h5>{{ __('Items') }}</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('Quantity') }}</th>
                            <th>{{ __('Unit Price') }}</th>
                            <th>{{ __('Discount') }}</th>
                            <th>{{ __('Tax %') }}</th>
                            <th>{{ __('Tax Amount') }}</th>
                            <th>{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchaseReturn->items as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->product->name }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ number_format($item->unit_price, 2) }}</td>
                                <td>{{ number_format($item->discount, 2) }}</td>
                                <td>{{ $item->tax }}%</td>
                                <td>{{ number_format($item->tax_amount, 2) }}</td>
                                <td>{{ number_format($item->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="7" class="text-right"><strong>{{ __('Subtotal') }}:</strong></td>
                            <td>{{ number_format($purchaseReturn->total, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="7" class="text-right"><strong>{{ __('Additional Discount') }}:</strong></td>
                            <td>{{ number_format($purchaseReturn->other_discount, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="7" class="text-right"><strong>{{ __('Grand Total') }}:</strong></td>
                            <td>{{ number_format($purchaseReturn->grand_total, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if ($purchaseReturn->payments->count() > 0)
                <h5 class="mt-4">{{ __('Payments') }}</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Payment Method') }}</th>
                                <th>{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($purchaseReturn->payments as $payment)
                                <tr>
                                    <td>{{ $payment->date }}</td>
                                    <td>{{ $payment->paymentMethod->name }}</td>
                                    <td>{{ number_format($payment->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="text-right"><strong>{{ __('Total Paid') }}:</strong></td>
                                <td>{{ number_format($purchaseReturn->paid, 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="2" class="text-right"><strong>{{ __('Balance') }}:</strong></td>
                                <td>{{ number_format($purchaseReturn->balance, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

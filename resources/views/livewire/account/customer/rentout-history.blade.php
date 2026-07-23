<div>
    <div class="panel">
        <div class="phead">
            <span class="ic"><i class="fa fa-key"></i></span>
            <div>
                <h4>Rentout / Sale History</h4>
                <span class="hint">Every rental and sale agreement for this customer</span>
            </div>
            <div class="right">
                <span class="tag mute">{{ $rentouts->count() }} {{ $rentouts->count() === 1 ? 'agreement' : 'agreements' }}</span>
                @if ($active_count)
                    <span class="tag ok">{{ $active_count }} active</span>
                @endif
            </div>
        </div>

        <div class="filters">
            <div class="row g-2">
            <div class="col-6 col-md-3 fld">
                <label>Agreement Type</label>
                <select wire:model.live="rentout_agreement_type">
                    <option value="">All Types</option>
                    @foreach (\App\Enums\RentOut\AgreementType::cases() as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 fld">
                <label>Status</label>
                <select wire:model.live="status">
                    <option value="">All Statuses</option>
                    @foreach (\App\Enums\RentOut\RentOutStatus::cases() as $case)
                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-6 fld">
                <label>Search</label>
                <input type="text" wire:model.live.debounce.400ms="search" placeholder="Agreement no, building, unit, group or type…">
            </div>
            </div>
        </div>

        <div class="pbody">
            <div class="row g-3">
                @forelse ($rentouts as $rentout)
                    @php
                        $isLease = $rentout->agreement_type === \App\Enums\RentOut\AgreementType::Lease;
                        $statusValue = $rentout->status?->value ?? (string) $rentout->status;
                        $isBooked = $statusValue === 'booked';
                        $prefix = $isLease ? 'property::sale::' : 'property::rent::';
                        $viewRoute = route($prefix . ($isBooked ? 'booking.view' : 'view'), $rentout->id);
                        $statusTag = match ($statusValue) {
                            'occupied' => 'ok',
                            'booked' => 'info',
                            'vacated' => 'warn',
                            'expired' => 'bad',
                            default => 'mute',
                        };
                    @endphp
                    <div class="col-12 col-md-6 col-xxl-4">
                        <div class="ag {{ $isLease ? 'sale' : '' }}">
                            <div class="h">
                                <span class="ic"><i class="fa {{ $isLease ? 'fa-key' : 'fa-home' }}"></i></span>
                                <div style="min-width: 0;">
                                    <div class="t">{{ $rentout->property?->number ? 'Unit ' . $rentout->property->number : 'Property' }}</div>
                                    <div class="s">{{ $rentout->building?->name ?: '—' }}</div>
                                </div>
                                <span class="tag {{ $isLease ? 'plum' : 'acc' }}" style="margin-left: auto;">
                                    {{ $rentout->agreement_type?->label() ?? $rentout->agreement_type }}
                                </span>
                            </div>
                            <div class="b">
                                <div class="r">
                                    <span><i class="fa fa-barcode"></i> Agreement</span>
                                    <span><a href="{{ $viewRoute }}" target="_blank">{{ $rentout->agreement_no }}</a></span>
                                </div>
                                <div class="r">
                                    <span><i class="fa fa-sitemap"></i> Group</span>
                                    <span>{{ $rentout->group?->name ?: '—' }}</span>
                                </div>
                                <div class="r">
                                    <span><i class="fa fa-tag"></i> Type</span>
                                    <span>{{ $rentout->type?->name ?: '—' }}</span>
                                </div>
                                <div class="r">
                                    <span><i class="fa fa-calendar"></i> Period</span>
                                    <span class="num">
                                        {{ $rentout->start_date ? systemDate($rentout->start_date) : '—' }} &rarr;
                                        {{ $rentout->end_date ? systemDate($rentout->end_date) : '—' }}
                                    </span>
                                </div>
                                <div class="r">
                                    <span><i class="fa fa-info-circle"></i> Status</span>
                                    <span><span class="tag {{ $statusTag }}">{{ $rentout->status?->label() ?? $rentout->status }}</span></span>
                                </div>
                            </div>
                            <div class="f">
                                <a href="{{ $viewRoute }}" target="_blank" class="btn sm">
                                    <i class="fa fa-eye"></i> {{ $isBooked ? 'Booking' : 'View' }}
                                </a>
                                @can('customer kyc.print')
                                    <a href="{{ route('account::customer::kyc', ['id' => $account_id, 'rentout' => $rentout->id]) }}" target="_blank"
                                        class="btn sm" title="Print KYC for this agreement">
                                        <i class="fa fa-file-pdf-o"></i> KYC
                                    </a>
                                @endcan
                            </div>
                    </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="empty">
                            <i class="fa fa-folder-open-o"></i>
                            No rentout or sale agreements found for this customer.
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

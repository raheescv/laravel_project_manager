{{-- Management Sections - Tabbed Navigation --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-2 border-bottom">
        <h6 class="mb-0 fw-semibold"><i class="fa fa-folder-open me-2"></i>Management Sections</h6>
    </div>
    <div class="card-body p-0">
        {{-- Tab Navigation --}}
        <ul class="nav nav-tabs nav-justified border-bottom" id="managementTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active py-3" id="payment-tab" data-bs-toggle="tab" data-bs-target="#PaymentTab" type="button" role="tab">
                    <i class="fa fa-credit-card d-block mb-1"></i>
                    <span class="small">Payment</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-3" id="payment-terms-tab" data-bs-toggle="tab" data-bs-target="#PaymentTermTab" type="button" role="tab">
                    <i class="fa fa-calendar d-block mb-1"></i>
                    <span class="small">Payment Terms</span>
                </button>
            </li>
            @if($isRental)
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-3" id="utilities-tab" data-bs-toggle="tab" data-bs-target="#UtilitiesTab" type="button" role="tab">
                        <i class="fa fa-bolt d-block mb-1"></i>
                        <span class="small">Utilities</span>
                    </button>
                </li>
            @endif
            <li class="nav-item" role="presentation">
                <button class="nav-link py-3" id="services-tab" data-bs-toggle="tab" data-bs-target="#ServicesTab" type="button" role="tab">
                    <i class="fa fa-cogs d-block mb-1"></i>
                    <span class="small">Services</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-3" id="cheques-tab" data-bs-toggle="tab" data-bs-target="#ChequeTab" type="button" role="tab">
                    <i class="fa fa-check-square-o d-block mb-1"></i>
                    <span class="small">Cheques</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-3" id="security-tab" data-bs-toggle="tab" data-bs-target="#SecurityTab" type="button" role="tab">
                    <i class="fa fa-shield d-block mb-1"></i>
                    <span class="small">Security</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-3" id="extend-tab" data-bs-toggle="tab" data-bs-target="#ExtendTab" type="button" role="tab">
                    <i class="fa fa-plus-circle d-block mb-1"></i>
                    <span class="small">Extend</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-3" id="notes-tab" data-bs-toggle="tab" data-bs-target="#NotesTab" type="button" role="tab">
                    <i class="fa fa-file-text-o d-block mb-1"></i>
                    <span class="small">Notes</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-3" id="transactions-tab" data-bs-toggle="tab" data-bs-target="#TransactionTab" type="button" role="tab">
                    <i class="fa fa-exchange d-block mb-1"></i>
                    <span class="small">Transactions</span>
                </button>
            </li>
        </ul>

        {{-- Tab Content --}}
        <div class="tab-content p-3" id="managementTabContent">
            {{-- Payment Tab --}}
            <div class="tab-pane fade show active" id="PaymentTab" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Due Date</th>
                                <th>Payment Mode</th>
                                <th class="text-end">Credit</th>
                                <th class="text-end">Debit</th>
                                <th>Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rentOut->journals as $index => $journal)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $journal->date?->format('d-m-Y') ?? '' }}</td>
                                    <td>{{ $journal->category ?? '' }}</td>
                                    <td>{{ $journal->due_date?->format('d-m-Y') ?? '' }}</td>
                                    <td>{{ $journal->payment_mode ?? '' }}</td>
                                    <td class="text-end text-success">{{ number_format($journal->credit ?? 0, 2) }}</td>
                                    <td class="text-end text-danger">{{ number_format($journal->debit ?? 0, 2) }}</td>
                                    <td>{{ $journal->remark ?? '' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-3">No payment records found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Payment Terms Tab --}}
            <div class="tab-pane fade" id="PaymentTermTab" role="tabpanel">
                {{-- Action Buttons --}}
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleSelectAllTerms()">
                        <i class="fa fa-check-square-o me-1"></i> Select All
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllTerms()">
                        <i class="fa fa-square-o me-1"></i> Deselect All
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteSelectedTerms()">
                        <i class="fa fa-trash me-1"></i> Delete Selected
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" wire:click="openSingleTermModal">
                        <i class="fa fa-plus me-1"></i> Add Single Term
                    </button>
                    <button type="button" class="btn btn-sm btn-success" wire:click="openMultipleTermModal">
                        <i class="fa fa-plus-circle me-1"></i> Add Multiple Term
                    </button>
                    <button type="button" class="btn btn-sm btn-warning" onclick="paySelectedTerms()">
                        <i class="fa fa-money me-1"></i> Pay Selected
                    </button>
                    <span class="btn btn-sm btn-outline-info disabled ms-auto">
                        {{ $rentOut->paymentTerms->count() }} rows
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-sm" id="paymentTermsTable">
                        <thead class="table-dark">
                            <tr>
                                <th style="width:30px;"><input type="checkbox" id="selectAllTermsCheckbox" onclick="toggleSelectAllTerms()"></th>
                                <th>#</th>
                                <th>Date</th>
                                <th>Label</th>
                                <th class="text-end">{{ $isRental ? 'Rent' : 'Installment' }}</th>
                                <th class="text-end">Discount</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Balance</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rentOut->paymentTerms as $index => $term)
                                @php
                                    $rowClass = match($term->paid_flag) {
                                        'Paid' => 'table-success',
                                        'Partially Paid' => 'table-info',
                                        'Current Pending' => '',
                                        'Pending' => 'table-danger',
                                        default => ''
                                    };
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td><input type="checkbox" class="term-checkbox" value="{{ $term->id }}"></td>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $term->due_date?->format('d-m-Y') }}</td>
                                    <td>{{ ucwords($term->label ?? '') }}</td>
                                    <td class="text-end">{{ number_format($term->amount, 2) }}</td>
                                    <td class="text-end">{{ number_format($term->discount, 2) }}</td>
                                    <td class="text-end">{{ number_format($term->total, 2) }}</td>
                                    <td class="text-end">{{ number_format($term->paid ?? 0, 2) }}</td>
                                    <td class="text-end">{{ number_format($term->balance ?? 0, 2) }}</td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-1" wire:click="editPaymentTerm({{ $term->id }})" title="Edit">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" wire:click="deletePaymentTerm({{ $term->id }})" wire:confirm="Are you sure you want to delete this payment term?" title="Delete">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="text-center text-muted py-3">No payment terms found</td></tr>
                            @endforelse
                        </tbody>
                        @if($rentOut->paymentTerms->count() > 0)
                            <tfoot class="table-light">
                                <tr class="fw-bold" style="color: red; font-size: 14px;">
                                    <td colspan="4" class="text-end">Total</td>
                                    <td class="text-end">{{ number_format($rentOut->paymentTerms->sum('amount'), 2) }}</td>
                                    <td class="text-end">{{ number_format($rentOut->paymentTerms->sum('discount'), 2) }}</td>
                                    <td class="text-end">{{ number_format($rentOut->paymentTerms->sum('total'), 2) }}</td>
                                    <td class="text-end">{{ number_format($rentOut->paymentTerms->sum('paid'), 2) }}</td>
                                    <td class="text-end">{{ number_format($rentOut->paymentTerms->sum('balance'), 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            {{-- Utilities Tab --}}
            @if($isRental)
                <div class="tab-pane fade" id="UtilitiesTab" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-sm">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Utility</th>
                                    <th>Date</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Balance</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rentOut->utilityTerms as $index => $uTerm)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $uTerm->utility?->name ?? '' }}</td>
                                        <td>{{ $uTerm->date?->format('d-m-Y') }}</td>
                                        <td class="text-end">{{ number_format($uTerm->amount, 2) }}</td>
                                        <td class="text-end {{ $uTerm->balance > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($uTerm->balance, 2) }}</td>
                                        <td>{{ $uTerm->remarks }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-3">No utility records found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Services Tab --}}
            <div class="tab-pane fade" id="ServicesTab" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Service Name</th>
                                <th class="text-end">Amount</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rentOut->services as $index => $service)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $service->name }}</td>
                                    <td class="text-end">{{ number_format($service->amount, 2) }}</td>
                                    <td>{{ $service->description }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">No services found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Cheques Tab --}}
            <div class="tab-pane fade" id="ChequeTab" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Cheque No</th>
                                <th>Bank</th>
                                <th>Date</th>
                                <th class="text-end">Amount</th>
                                <th>Payee</th>
                                <th>Status</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rentOut->cheques as $index => $cheque)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $cheque->cheque_no }}</td>
                                    <td>{{ $cheque->bank_name }}</td>
                                    <td>{{ $cheque->date?->format('d-m-Y') }}</td>
                                    <td class="text-end">{{ number_format($cheque->amount, 2) }}</td>
                                    <td>{{ $cheque->payee_name }}</td>
                                    <td>
                                        @if($cheque->status)
                                            <span class="badge bg-{{ $cheque->status->color() }}">{{ $cheque->status->label() }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $cheque->remarks }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-3">No cheques found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Security Tab --}}
            <div class="tab-pane fade" id="SecurityTab" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th class="text-end">Amount</th>
                                <th>Payment Mode</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Due Date</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rentOut->securities as $index => $security)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td class="text-end">{{ number_format($security->amount, 2) }}</td>
                                    <td>{{ $security->payment_mode?->label() }}</td>
                                    <td>{{ $security->type?->label() }}</td>
                                    <td>
                                        <span class="badge bg-{{ $security->status?->color() }}">
                                            {{ $security->status?->label() }}
                                        </span>
                                    </td>
                                    <td>{{ $security->due_date?->format('d-m-Y') }}</td>
                                    <td>{{ $security->remarks }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-3">No security deposits found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Extend Tab --}}
            <div class="tab-pane fade" id="ExtendTab" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th class="text-end">Rent Amount</th>
                                <th>Payment Mode</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rentOut->extends as $index => $extend)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $extend->start_date?->format('d-m-Y') }}</td>
                                    <td>{{ $extend->end_date?->format('d-m-Y') }}</td>
                                    <td class="text-end">{{ number_format($extend->rent_amount, 2) }}</td>
                                    <td>{{ $extend->payment_mode?->label() }}</td>
                                    <td>{{ $extend->remarks }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-3">No extensions found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Notes Tab --}}
            <div class="tab-pane fade" id="NotesTab" role="tabpanel">
                {{-- Add Note --}}
                <div class="d-flex gap-2 mb-3">
                    <input type="text" class="form-control form-control-sm" wire:model="newNote" placeholder="Add a note..." wire:keydown.enter="addNote">
                    <button type="button" class="btn btn-sm btn-primary" wire:click="addNote">
                        <i class="fa fa-plus me-1"></i> Add
                    </button>
                </div>
                @forelse($rentOut->notes as $note)
                    <div class="card mb-2 border">
                        <div class="card-body py-2 px-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="mb-0 small">{{ $note->note }}</p>
                                    @if($note->creator)
                                        <small class="text-muted">&mdash; {{ $note->creator->name }}</small>
                                    @endif
                                </div>
                                <div class="d-flex align-items-center gap-2 ms-3">
                                    <small class="text-muted text-nowrap">{{ $note->created_at?->format('d-m-Y H:i') }}</small>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" wire:click="deleteNote({{ $note->id }})" wire:confirm="Delete this note?">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">No notes found</div>
                @endforelse
            </div>

            {{-- Transactions Tab --}}
            <div class="tab-pane fade" id="TransactionTab" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Payment Mode</th>
                                <th class="text-end">Credit</th>
                                <th class="text-end">Debit</th>
                                <th>Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rentOut->journals as $index => $journal)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $journal->date?->format('d-m-Y') ?? '' }}</td>
                                    <td>{{ $journal->category ?? '' }}</td>
                                    <td>{{ $journal->payment_mode ?? '' }}</td>
                                    <td class="text-end text-success">{{ number_format($journal->credit ?? 0, 2) }}</td>
                                    <td class="text-end text-danger">{{ number_format($journal->debit ?? 0, 2) }}</td>
                                    <td>{{ $journal->remark ?? '' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-3">No transactions found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

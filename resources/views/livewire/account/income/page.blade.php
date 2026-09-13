<div class="exx">
    <x-account.journal-form.premium />

    @php
        $currencyCode = base_currency()['code'] ?? null;
        $amountValue = (float) ($journals['amount'] ?? 0);
        $dateLabel = ! empty($journals['date']) ? \Carbon\Carbon::parse($journals['date'])->format('D, j M Y') : '—';
    @endphp

    <!-- ═══════════  HERO  ═══════════ -->
    <div class="exx-hero">
        <span class="exx-glow a"></span>
        <span class="exx-glow b"></span>
        <div class="exx-hero-row">
            <div class="exx-hero-id">
                <span class="exx-hero-ic">
                    <i class="fa {{ $table_id ? 'fa-pencil-square-o' : 'fa-plus-circle' }}"></i>
                </span>
                <div>
                    <div class="exx-eyebrow">Accounts &middot; Income</div>
                    <h1 class="exx-hero-title">{{ $table_id ? 'Edit Income' : 'Record Income' }}</h1>
                </div>
            </div>
            <div class="exx-hero-tools">
                <span class="exx-pill">
                    <span class="dot"></span>
                    {{ $table_id ? 'Editing #'.$table_id : 'New' }}
                </span>
                <button type="button" class="exx-x" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <form wire:submit="save">
        <!-- ═══════════  BODY  ═══════════ -->
        <div class="exx-body">
            @if ($this->getErrorBag()->count())
                <div class="exx-errors">
                    <i class="fa fa-exclamation-triangle exx-errors-ic"></i>
                    <div>
                        <div class="exx-errors-title">Please correct the following:</div>
                        <ul class="exx-errors-list">
                            @foreach ($this->getErrorBag()->toArray() as $field => $fieldErrors)
                                <li>{{ $fieldErrors[0] }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Amount headline -->
            <div class="exx-amount-card @error('journals.amount') is-invalid @enderror">
                <div class="exx-amount-top">
                    <label for="income_amount" class="exx-label"><i class="fa fa-money"></i> Amount <span class="req">*</span></label>
                    <span class="exx-count">total received incl. tax &middot; min 1 &middot; max 999,999</span>
                </div>
                <div class="exx-amount-line">
                    <span class="exx-amount-code">{{ $currencyCode ?: '' }}@if (! $currencyCode)<i class="fa fa-money"></i>@endif</span>
                    <input type="number" id="income_amount" class="exx-amount-big" inputmode="decimal" step="0.01" min="1" max="999999" placeholder="0.00" wire:model="journals.amount" autocomplete="off">
                </div>
                @error('journals.amount')
                    <div class="exx-err"><i class="fa fa-exclamation-circle"></i> {{ $message }}</div>
                @enderror
            </div>

            <!-- One row per question -->
            <div class="exx-rows">
                <div class="exx-row top">
                    <div class="exx-row-l">
                        <span class="exx-row-ic"><i class="fa fa-tags"></i></span>
                        <span class="exx-row-t">Category <span class="req">*</span></span>
                    </div>
                    <div class="exx-row-r">
                        <div class="exx-inline">
                            <div class="exx-grow" wire:ignore>
                                <select id="income_category_id" class="select-account_id" account_type="income" placeholder="Search income category…" wire:model="journals.credit"></select>
                            </div>
                            @if (count($recentCategories))
                                <div class="exx-chips">
                                    <span class="exx-chips-k">Recent</span>
                                    @foreach ($recentCategories as $recent)
                                        <button type="button" class="exx-chip exx-recent {{ ($journals['credit'] ?? null) == $recent['id'] ? 'on' : '' }}" data-id="{{ $recent['id'] }}" data-name="{{ $recent['name'] }}">
                                            <i class="fa fa-check"></i>{{ $recent['name'] }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @error('journals.credit')
                            <div class="exx-err"><i class="fa fa-exclamation-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="exx-row top">
                    <div class="exx-row-l">
                        <span class="exx-row-ic"><i class="fa fa-credit-card"></i></span>
                        <span class="exx-row-t">Received in <span class="req">*</span></span>
                    </div>
                    <div class="exx-row-r">
                        <div wire:ignore>
                            <select id="income_payment_method_id" class="select-payment_method_id-list" placeholder="Choose payment method…" wire:model="journals.debit">
                                @foreach ($paymentMethods ?? [] as $id => $name)
                                    <option value="{{ $id }}" @selected($id == ($default_payment_method_id ?? null))>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('journals.debit')
                            <div class="exx-err"><i class="fa fa-exclamation-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="exx-row top">
                    <div class="exx-row-l">
                        <span class="exx-row-ic"><i class="fa fa-calendar"></i></span>
                        <span class="exx-row-t">Date <span class="req">*</span></span>
                    </div>
                    <div class="exx-row-r">
                        <div class="exx-inline">
                            <div class="exx-input exx-fixed @error('journals.date') is-invalid @enderror">
                                <input type="date" id="income_date" class="exx-control" wire:model="journals.date">
                            </div>
                            <div class="exx-chips" wire:ignore>
                                <button type="button" class="exx-chip exx-date-chip" data-shift="0"><i class="fa fa-check"></i>Today</button>
                                <button type="button" class="exx-chip exx-date-chip" data-shift="-1"><i class="fa fa-check"></i>Yesterday</button>
                            </div>
                        </div>
                        @error('journals.date')
                            <div class="exx-err"><i class="fa fa-exclamation-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="exx-row full">
                    <div class="exx-pair">
                        <div>
                            <div class="exx-input @error('journals.person_name') is-invalid @enderror">
                                <span class="exx-prefix"><i class="fa fa-user"></i> Payer</span>
                                <input type="text" id="income_person_name" class="exx-control" maxlength="30" placeholder="Customer or person" data-exx-count="payee" wire:model="journals.person_name" autocomplete="off">
                                <span class="exx-suffix exx-count" wire:ignore data-exx-count-out="payee">{{ mb_strlen($journals['person_name'] ?? '') }}/30</span>
                            </div>
                            @error('journals.person_name')
                                <div class="exx-err"><i class="fa fa-exclamation-circle"></i> {{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <div class="exx-input @error('journals.reference_number') is-invalid @enderror">
                                <span class="exx-prefix"><i class="fa fa-hashtag"></i> Ref</span>
                                <input type="text" id="income_reference_number" class="exx-control" maxlength="30" placeholder="Bill / receipt no." data-exx-count="ref" wire:model="journals.reference_number" autocomplete="off">
                                <span class="exx-suffix exx-count" wire:ignore data-exx-count-out="ref">{{ mb_strlen($journals['reference_number'] ?? '') }}/30</span>
                            </div>
                            @error('journals.reference_number')
                                <div class="exx-err"><i class="fa fa-exclamation-circle"></i> {{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="exx-row top">
                    <div class="exx-row-l">
                        <span class="exx-row-ic"><i class="fa fa-pencil"></i></span>
                        <span class="exx-row-t">Description <span class="req">*</span></span>
                    </div>
                    <div class="exx-row-r">
                        <div class="exx-input area @error('journals.description') is-invalid @enderror">
                            <textarea id="income_description" class="exx-control" rows="2" maxlength="100" placeholder="e.g. Alteration charges for walk-in customer" data-exx-count="desc" wire:model="journals.description"></textarea>
                            <span class="exx-suffix exx-count" wire:ignore data-exx-count-out="desc">{{ mb_strlen($journals['description'] ?? '') }}/100</span>
                        </div>
                        @error('journals.description')
                            <div class="exx-err"><i class="fa fa-exclamation-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- What will be recorded (kept by JS; server paints the first state) -->
            <div class="exx-sentence" wire:ignore>
                <span class="exx-sentence-ic"><i class="fa fa-book"></i></span>
                <div class="exx-sentence-t">
                    Recording
                    <b data-exx-out="amount" class="{{ $amountValue >= 1 ? '' : 'empty' }}">{{ $amountValue >= 1 ? trim($currencyCode.' '.currency($amountValue)) : 'an amount' }}</b>
                    for
                    <b data-exx-out="cat" class="{{ ! empty($journals['credit_name']) ? '' : 'empty' }}">{{ $journals['credit_name'] ?? 'a category' }}</b>,
                    received in
                    <b data-exx-out="method" class="{{ ! empty($journals['debit_name']) ? '' : 'empty' }}">{{ $journals['debit_name'] ?? 'a payment method' }}</b>
                    on
                    <b data-exx-out="date">{{ $dateLabel }}</b>.
                </div>
            </div>
        </div>

        <!-- ═══════════  FOOTER  ═══════════ -->
        <div class="exx-footer">
            <button type="button" class="exx-btn ghost" data-bs-dismiss="modal">
                <i class="fa fa-times"></i> Cancel
            </button>
            <div class="exx-footer-right">
                @if (! $table_id)
                    <button type="button" wire:click="save(1)" class="exx-btn soft" wire:loading.attr="disabled" wire:target="save">
                        <i class="fa fa-plus"></i> Save &amp; Add New
                    </button>
                @endif
                <button type="submit" class="exx-btn primary" wire:loading.attr="disabled" wire:target="save">
                    <i class="fa fa-check" wire:loading.remove wire:target="save"></i>
                    <i class="fa fa-spinner fa-spin" wire:loading wire:target="save"></i>
                    {{ $table_id ? 'Update Income' : 'Save Income' }}
                    <span class="exx-kbd">&#9166;</span>
                </button>
            </div>
        </div>
    </form>

    @push('scripts')
        <script>
            $(document).ready(function() {
                window.exxForm({
                    modal: '#IncomeModal',
                    wireId: @json($this->getId()),
                    today: @json(date('Y-m-d')),
                    currency: @json($currencyCode ?? ''),
                    decimals: @json(currency_decimals()),
                    ids: { category: 'income_category_id', method: 'income_payment_method_id', amount: 'income_amount', date: 'income_date' },
                    categoryKey: 'credit',
                    methodKey: 'debit',
                    toggleEvent: 'ToggleIncomeModal',
                });
            });
        </script>
    @endpush
</div>

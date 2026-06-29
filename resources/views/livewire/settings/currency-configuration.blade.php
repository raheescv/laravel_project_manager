<div class="card shadow-sm border-0">
    <style>
        .cur-cfg-hint { font-size: .78rem; color: var(--bs-secondary-color); }
        .cur-cfg-hint code { background: var(--bs-tertiary-bg); padding: .05rem .35rem; border-radius: 5px; }
        .cur-table { font-size: .85rem; }
        .cur-table th { font-size: .68rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--bs-secondary-color); white-space: nowrap; }
        .cur-table td { vertical-align: middle; }
        .cur-table .form-control, .cur-table .form-select { font-size: .85rem; }
        .cur-table .col-code { width: 6.5rem; }
        .cur-table .col-sym { width: 5rem; }
        .cur-table .col-dec { width: 5rem; }
        .cur-table .col-rate { width: 8rem; }
        .cur-table tr.is-base { background: color-mix(in srgb, var(--bs-primary), transparent 93%); }
        .cur-rate-locked { display: inline-flex; align-items: center; gap: .3rem; color: var(--bs-secondary-color); font-variant-numeric: tabular-nums; }
        .base-radio { width: 1.1rem; height: 1.1rem; accent-color: var(--bs-primary); cursor: pointer; }
    </style>

    <div class="card-header bg-primary text-white py-2 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 text-white"><i class="fa fa-money me-1"></i> Currencies &amp; Exchange Rates</h5>
        <span class="badge bg-light text-primary">Source of truth</span>
    </div>

    <form wire:submit="save">
        <div class="card-body p-3">
            <div class="row g-3 align-items-end mb-3">
                <div class="col-12 col-md-5">
                    <label class="form-label fw-medium small mb-1">Base currency</label>
                    <select class="form-select form-select-sm" wire:model.live="base_index">
                        @foreach ($currencies as $i => $row)
                            <option value="{{ $i }}">{{ strtoupper($row['code'] ?: 'New currency') }} {{ $row['name'] ? '— '.$row['name'] : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-7">
                    <p class="cur-cfg-hint mb-0">
                        Rates are stored as <code>rate_to_base</code> — how many <b>base</b> units one unit of each
                        currency is worth. The base currency is locked at <code>1.0000</code>. Saving publishes the
                        list to the mobile app via the API for offline use.
                    </p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle cur-table mb-0">
                    <thead>
                        <tr>
                            <th class="text-center">Base</th>
                            <th>Code</th>
                            <th class="text-center">Symbol</th>
                            <th>Name</th>
                            <th class="text-center">Decimals</th>
                            <th class="text-end">Rate&nbsp;&rarr;&nbsp;base</th>
                            <th class="text-center">Active</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($currencies as $i => $row)
                            <tr wire:key="cur-{{ $i }}" class="{{ $i === $base_index ? 'is-base' : '' }}">
                                <td class="text-center">
                                    <input type="radio" class="base-radio" name="base_currency_radio"
                                        value="{{ $i }}" @checked($i === $base_index)
                                        wire:click="setBase({{ $i }})" title="Set as base currency">
                                </td>
                                <td class="col-code">
                                    <input type="text" class="form-control form-control-sm text-uppercase fw-semibold"
                                        maxlength="6" wire:model="currencies.{{ $i }}.code" placeholder="USD">
                                </td>
                                <td class="col-sym">
                                    <input type="text" class="form-control form-control-sm text-center"
                                        maxlength="8" wire:model="currencies.{{ $i }}.symbol" placeholder="$">
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm"
                                        wire:model="currencies.{{ $i }}.name" placeholder="US Dollar">
                                </td>
                                <td class="col-dec text-center">
                                    <input type="number" min="0" max="6" step="1"
                                        class="form-control form-control-sm text-center"
                                        wire:model="currencies.{{ $i }}.decimals">
                                </td>
                                <td class="col-rate text-end">
                                    @if ($i === $base_index)
                                        <span class="cur-rate-locked justify-content-end w-100">
                                            <i class="fa fa-lock"></i> 1.0000
                                        </span>
                                    @else
                                        <input type="number" min="0" step="0.0001"
                                            class="form-control form-control-sm text-end"
                                            wire:model="currencies.{{ $i }}.rate_to_base" placeholder="0.0000">
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-inline-block m-0">
                                        <input type="checkbox" class="form-check-input"
                                            wire:model="currencies.{{ $i }}.active"
                                            @disabled($i === $base_index)>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-link text-danger p-1"
                                        wire:click="removeCurrency({{ $i }})"
                                        @disabled($i === $base_index)
                                        title="{{ $i === $base_index ? 'Base currency cannot be removed' : 'Remove' }}">
                                        <i class="fa fa-trash-o"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-body-secondary py-3">No currencies configured.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <button type="button" class="btn btn-sm btn-outline-primary" wire:click="addCurrency">
                    <i class="fa fa-plus me-1"></i> Add currency
                </button>
            </div>
        </div>

        <div class="card-footer bg-light d-flex justify-content-between align-items-center py-2 px-3">
            <small class="text-body-secondary">
                <i class="fa fa-mobile me-1"></i> Published to <code>GET /api/v1/settings/currencies</code>
            </small>
            <button type="submit" class="btn btn-primary btn-sm px-3">
                <i class="fa fa-save me-1"></i> Save Changes
            </button>
        </div>
    </form>
</div>

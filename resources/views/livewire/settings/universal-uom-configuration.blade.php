<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white py-2">
        <h5 class="mb-0 text-white">{{ __('Universal Unit of Measure (UOM)') }}</h5>
    </div>
    <div class="card-body p-3">
        <p class="text-body-secondary small mb-3">
            {{ __('Define default unit conversions (e.g. 1 Liter = 1000 ml). These apply to products that do not have their own unit conversions. Products with their own Product Units will use those instead.') }}
        </p>

        <div class="row g-2 mb-4">
            <div class="col-12 col-md-4">
                <label class="form-label fw-medium small mb-1" for="base_unit_id">{{ __('Base unit') }}</label>
                <div wire:ignore>
                    <select id="base_unit_id" class="tomSelect">
                        <option value="">{{ __('Select base unit') }}</option>
                        @foreach ($this->unitsList as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @error('base_unit_id')
                    <span class="text-danger small">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-medium small mb-1" for="sub_unit_id">{{ __('Sub unit') }}</label>
                <div wire:ignore>
                    <select id="sub_unit_id" class="tomSelect">
                        <option value="">{{ __('Select sub unit') }}</option>
                        @foreach ($this->unitsList as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @error('sub_unit_id')
                    <span class="text-danger small">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label fw-medium small mb-1" for="conversion_factor">{{ __('Factor') }}</label>
                <input type="number" step="0.0001" min="0.0001" id="conversion_factor" wire:model="conversion_factor" class="form-control form-control-sm" placeholder="e.g. 1000">
                @error('conversion_factor')
                    <span class="text-danger small">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-12 col-md-2 d-flex align-items-end">
                <button type="button" wire:click="addConversion" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus me-1"></i>{{ __('Add') }}
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Base unit') }}</th>
                        <th>{{ __('Sub unit') }}</th>
                        <th class="text-end">{{ __('Conversion (1 base = factor × sub)') }}</th>
                        <th class="text-end" style="width: 100px;">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->conversions as $row)
                        <tr>
                            <td>{{ $row->baseUnit?->name }} ({{ $row->baseUnit?->code }})</td>
                            <td>{{ $row->subUnit?->name }} ({{ $row->subUnit?->code }})</td>
                            <td class="text-end">{{ $row->conversion_factor }}</td>
                            <td class="text-end">
                                <button type="button" wire:click="removeConversion({{ $row->id }})" wire:confirm="{{ __('Remove this conversion?') }}" class="btn btn-outline-danger btn-sm">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-body-secondary py-3">{{ __('No universal UOM conversions defined yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#base_unit_id').change(function() {
                    @this.set('base_unit_id', $(this).val());
                });
                $('#sub_unit_id').change(function() {
                    @this.set('sub_unit_id', $(this).val());
                });
            });
        </script>
    @endpush
</div>

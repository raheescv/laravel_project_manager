<div>
    <div wire:ignore.self class="modal fade" id="TailoringMeasurementEditModal" tabindex="-1" aria-labelledby="TailoringMeasurementEditModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-bottom">
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="TailoringMeasurementEditModalLabel">
                            <i class="fa fa-sliders me-1"></i>Edit Measurements
                        </h5>
                        <div class="small text-secondary fw-semibold mt-1">{{ $measurementModalItemTitle ?: 'Select item' }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-body-tertiary p-4">
                    @if (!empty($measurementModalMeta))
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="badge text-dark bg-white border border-secondary-subtle rounded-pill px-3 py-2"><i class="fa fa-folder-open-o me-1"></i>{{ $measurementModalMeta['category_name'] ?? 'Category' }}</span>
                            <span class="badge text-dark bg-white border border-secondary-subtle rounded-pill px-3 py-2"><i class="fa fa-object-group me-1"></i>{{ $measurementModalMeta['model_name'] ?? 'Model' }}</span>
                            @if (!empty($measurementModalMeta['model_type_name']))
                                <span class="badge text-dark bg-white border border-secondary-subtle rounded-pill px-3 py-2"><i class="fa fa-code-fork me-1"></i>{{ $measurementModalMeta['model_type_name'] }}</span>
                            @endif
                        </div>
                    @endif

                    <div class="card border shadow-sm mb-4">
                        <div class="card-body py-3">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-9">
                                    <label class="form-label text-uppercase text-secondary fw-bold small mb-1">
                                        <i class="fa fa-clone me-1"></i>Copy Measurements From Other Item
                                    </label>
                                    <select class="form-select" wire:model="measurementCopySourceItemId"
                                        @disabled(empty($measurementCopyOptions))>
                                        <option value="">Select source item</option>
                                        @foreach ($measurementCopyOptions as $option)
                                            <option value="{{ $option['id'] }}">
                                                {{ $option['label'] }} | {{ $option['model'] }} / {{ $option['model_type'] }}
                                                @if (!empty($option['preview']))
                                                    | {{ $option['preview'] }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @if (empty($measurementCopyOptions))
                                        <div class="small text-muted mt-1">No other items available in this category.</div>
                                    @endif
                                </div>
                                <div class="col-md-3 d-grid">
                                    <button type="button" class="btn btn-outline-primary"
                                        wire:click="applyMeasurementsFromSource"
                                        @disabled(empty($measurementCopyOptions))>
                                        <i class="fa fa-arrow-down me-1"></i>Apply
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    @php
                        $modalSectionLabels = [
                            'basic_body' => ['label' => 'Dimensions', 'icon' => 'fa-arrows-h'],
                            'collar_cuff' => ['label' => 'Components', 'icon' => 'fa-puzzle-piece'],
                            'specifications' => ['label' => 'Styles & Models', 'icon' => 'fa-cut'],
                        ];
                    @endphp

                    <div class="row g-4">
                        @forelse ($measurementModalSections as $sectionId => $fields)
                            @php $sectionMeta = $modalSectionLabels[$sectionId] ?? ['label' => ucwords(str_replace('_', ' ', $sectionId)), 'icon' => 'fa-list']; @endphp
                            <div class="col-6">
                                <div class="card border shadow-sm">
                                    <div class="card-header bg-white fw-bold fs-5">
                                        <i class="fa {{ $sectionMeta['icon'] }} me-1"></i>{{ $sectionMeta['label'] }}
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            @foreach ($fields as $field)
                                                <div class="col-md-6">
                                                    <label class="form-label text-uppercase text-secondary fw-bold small mb-1">{{ $field['label'] }}</label>
                                                    @if (($field['field_type'] ?? 'text') === 'select')
                                                        <select class="form-select"
                                                            wire:model.defer="measurementModalForm.{{ $field['field_key'] }}">
                                                            <option value="">Select {{ $field['label'] }}</option>
                                                            @foreach (($measurementModalOptions[$field['field_key']] ?? []) as $optionValue => $optionLabel)
                                                                <option value="{{ $optionLabel }}">{{ $optionLabel }}</option>
                                                            @endforeach
                                                        </select>
                                                    @else
                                                        <input type="text" class="form-control"
                                                            wire:model.defer="measurementModalForm.{{ $field['field_key'] }}"
                                                            placeholder="Enter {{ strtolower($field['label']) }}">
                                                    @endif
                                                    @error('measurementModalForm.' . $field['field_key'])
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-secondary mb-0">No measurements available for editing.</div>
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-4">
                        <label class="form-label text-uppercase text-secondary fw-bold small mb-1"><i class="fa fa-sticky-note-o me-1"></i>Special Instructions</label>
                        <textarea class="form-control" rows="3" wire:model.defer="measurementModalNotes"
                            placeholder="Add notes for this item"></textarea>
                        @error('measurementModalNotes')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer bg-white border-top">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary px-4" wire:click="saveMeasurementModal">
                        <i class="fa fa-save me-1"></i>Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('tailoring-measurement-modal-open', () => {
            const modalEl = document.getElementById('TailoringMeasurementEditModal');
            if (!modalEl) return;
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });

        window.addEventListener('tailoring-measurement-modal-close', () => {
            const modalEl = document.getElementById('TailoringMeasurementEditModal');
            if (!modalEl) return;
            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        });
    </script>
</div>
